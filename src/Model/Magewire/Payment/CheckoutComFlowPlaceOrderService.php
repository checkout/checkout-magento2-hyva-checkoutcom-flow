<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Model\Magewire\Payment;

use Exception;
use Hyva\Checkout\Model\Magewire\Component\Evaluation\Executable;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractPlaceOrderService;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Hyva\Checkout\Model\Magewire\Payment\AbstractOrderData;

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
class CheckoutComFlowPlaceOrderService extends AbstractPlaceOrderService
{
    public const FLOW_EXECUTABLE_NAME = 'make:checkoutcom:flow-payment';

    public function __construct(
        CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        ?AbstractOrderData $orderData = null
    ) {
        parent::__construct($cartManagement, $orderData);
    }

    /**
     * The Flow SDK handles the redirect to verifyfloworder via onPaymentCompleted after 3DS.
     * Returning true here would cause Magewire to push a PlaceOrderRedirect to checkout/onepage/success
     * before the payment is processed.
     */
    public function canRedirect(): bool
    {
        return false;
    }

    public function evaluateCompletion(
        EvaluationResultFactory $resultFactory,
        ?int $orderId = null
    ): EvaluationResultInterface
    {
        if ($orderId === null) {
            return parent::evaluateCompletion($resultFactory, $orderId);
        }

        /** @var Executable $executable */
        $executable = $resultFactory->createExecutable(self::FLOW_EXECUTABLE_NAME);
        $executable->withParams($this->buildExecutableParams($orderId));

        return $executable;
    }

    private function buildExecutableParams(int $orderId): array
    {
        $params = ['orderId' => $orderId];

        try {
            $order = $this->orderRepository->get($orderId);
            $params['reference'] = (string)$order->getIncrementId();
        } catch (Exception) {
            $params['reference'] = null;
        }

        return $params;
    }
}
