<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Model\Magewire\Payment;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Helper\Utilities;
use CheckoutCom\Magento2\Model\Methods\VaultMethod;
use CheckoutCom\Magento2\Model\Service\ApiHandlerService;
use CheckoutCom\Magento2\Model\Service\MethodHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderHandlerService;
use CheckoutCom\Magento2\Model\Service\OrderStatusHandlerService;
use CheckoutCom\Magento2\Model\Service\PaymentErrorHandlerService;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\Executable;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractOrderData;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Checkout.com Magento 2 Magento2 Payment.
 *
 * PHP version 8
 *
 * @category  Checkout.com
 * @package   Magento2
 * @author    Checkout.com Development Team <integration@checkout.com>
 * @copyright 2010-present Checkout.com all rights reserved
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://www.checkout.com
 */
class CheckoutComVaultPlaceOrderService extends AbstractPlaceOrderService
{
    public const ADDITIONAL_INFO_REDIRECT_URL = 'checkoutcom_vault_redirect_url';
    public const VAULT_3DS_EXECUTABLE_NAME = 'make:checkoutcom:vault-3ds';

    public function __construct(
        CartManagementInterface $cartManagement,
        private readonly OrderHandlerService $orderHandler,
        private readonly OrderStatusHandlerService $orderStatusHandler,
        private readonly MethodHandlerService $methodHandler,
        private readonly ApiHandlerService $apiHandler,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Utilities $utilities,
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly LoggerInterface $logger,
        ?AbstractOrderData $orderData = null
    ) {
        parent::__construct($cartManagement, $orderData);
    }

    public function canRedirect(): bool
    {
        return false;
    }

    public function placeOrder(Quote $quote): int
    {
        $publicHash = (string)$quote->getPayment()->getAdditionalInformation('public_hash');
        if ($publicHash === '') {
            throw new LocalizedException(__('No saved card selected for the vault payment.'));
        }

        $order = $this->orderHandler->setMethodId(VaultMethod::CODE)->handleOrder($quote);
        if (!$this->orderHandler->isOrder($order)) {
            throw new LocalizedException(__('The order could not be created for the vault payment.'));
        }

        $data = ['methodId' => VaultMethod::CODE, 'publicHash' => $publicHash];
        $cvv = (string)$quote->getPayment()->getAdditionalInformation('cvv');
        if ($cvv !== '') {
            $data['cvv'] = $cvv;
        }

        $amount = (float)$order->getGrandTotal();
        $currency = (string)$order->getOrderCurrencyCode();
        $reference = (string)$order->getIncrementId();

        try {
            $response = $this->methodHandler->get(VaultMethod::CODE)->sendPaymentRequest(
                $data,
                $amount,
                $currency,
                $reference,
                $quote
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                '[CheckoutCom Vault] Payment request failed: ' . $exception->getMessage()
            );
            $this->rollBackOrder($order);
            throw new LocalizedException(__('The vault payment could not be processed.'));
        }

        if (isset($response['_links']['redirect']['href'])) {
            $order = $this->utilities->setPaymentData($order, $response, $data);
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $order->getPayment()->setAdditionalInformation(
                self::ADDITIONAL_INFO_REDIRECT_URL,
                (string)$response['_links']['redirect']['href']
            );
            $this->orderRepository->save($order);

            return (int)$order->getEntityId();
        }

        $storeCode = (string)$order->getStore()->getCode();
        $api = $this->apiHandler->init($storeCode, ScopeInterface::SCOPE_STORE);
        $isValidResponse = $api->isValidResponse($response);

        $actions = $response['actions'] ?? [];
        $responseCode = !empty($actions)
            ? (string)($actions[0]['response_code'] ?? '')
            : (string)($response['response_code'] ?? '');

        if (!$isValidResponse || !$this->isAuthorized($responseCode)) {
            $this->logger->error('[CheckoutCom Vault] Payment declined', [
                'order_id' => $reference,
                'is_valid' => $isValidResponse,
                'response_code' => $responseCode,
                'response_keys' => array_keys($response),
                'http_status' => $response['http_metadata']?->getStatusCode() ?? null,
            ]);
            $this->rollBackOrder($order);
            throw new LocalizedException(__('The vault payment was declined.'));
        }

        $order = $this->utilities->setPaymentData($order, $response, $data);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);

        $this->orderRepository->save($order);

        return (int)$order->getEntityId();
    }

    public function getRedirectUrl(Quote $quote, ?int $orderId = null): string
    {
        if ($orderId !== null) {
            try {
                $order = $this->orderRepository->get($orderId);
                $redirectUrl = (string)$order->getPayment()
                    ->getAdditionalInformation(self::ADDITIONAL_INFO_REDIRECT_URL);
                if ($redirectUrl !== '') {
                    return $redirectUrl;
                }
            } catch (NoSuchEntityException) {
                // fall through to parent (standard success page)
            }
        }

        return parent::getRedirectUrl($quote, $orderId);
    }

    public function evaluateCompletion(
        EvaluationResultFactory $resultFactory,
        ?int $orderId = null
    ): EvaluationResultInterface {
        if ($orderId === null) {
            return parent::evaluateCompletion($resultFactory, $orderId);
        }

        $redirectUrl = null;
        try {
            $order = $this->orderRepository->get($orderId);
            $url = (string)$order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFO_REDIRECT_URL);
            if ($url !== '') {
                $redirectUrl = $url;
            }
        } catch (NoSuchEntityException $e) {
            $this->logger->warning(
                '[CheckoutCom Vault] Order not found in evaluateCompletion: ' . $e->getMessage(),
                ['order_id' => $orderId]
            );
        }

        $executable = $resultFactory->createExecutable(self::VAULT_3DS_EXECUTABLE_NAME);
        $executable->withParams(['redirectUrl' => $redirectUrl]);
        return $executable;
    }

    private function rollBackOrder(OrderInterface $order): void
    {
        if ($this->config->isPaymentWithOrderFirst()) {
            try {
                $this->orderStatusHandler->handleFailedPayment($order);
            } catch (Throwable $exception) {
                $this->logger->error(
                    '[CheckoutCom Vault] Failed to mark order as failed: ' . $exception->getMessage()
                );
            }
        }

        try {
            $this->orderHandler->deleteOrder($order);
        } catch (Throwable $exception) {
            $this->logger->error(
                '[CheckoutCom Vault] Failed to delete order after failed payment: ' . $exception->getMessage()
            );
        }

        try {
            $this->checkoutSession->restoreQuote();
        } catch (Throwable $exception) {
            $this->logger->error(
                '[CheckoutCom Vault] Failed to restore quote after failed payment: ' . $exception->getMessage()
            );
        }
    }

    private function isAuthorized(string $responseCode): bool
    {
        return $responseCode !== ''
            && mb_substr($responseCode, 0, 2) === PaymentErrorHandlerService::TRANSACTION_SUCCESS_DIGITS;
    }
}
