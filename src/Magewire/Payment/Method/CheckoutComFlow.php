<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Magewire\Payment\Method;

use CheckoutCom\Magento2\Model\Methods\FlowMethod;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;

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
class CheckoutComFlow extends Component
{
    public const ADDITIONAL_INFO_SCHEME = 'preferred_scheme';
    public const ADDITIONAL_INFO_FLOW_METHOD_ID = 'flow_method_id';

    public ?string $paymentScheme = null;

    protected $loader = [
        'saveCard' => 'Saving card preference',
    ];

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly CustomerSession $customerSession,
        private readonly FlowGeneralSettings $flowGeneralSettings,
        private readonly LoggerInterface $logger
    ) {
    }

    public function setScheme(string|array|null $scheme): ?string
    {
        $scheme = $this->normalizeScheme($scheme);
        $this->paymentScheme = $scheme;

        try {
            $quote = $this->checkoutSession->getQuote();
            $payment = $quote->getPayment();

            $payment->setAdditionalInformation(self::ADDITIONAL_INFO_SCHEME, $scheme);

            if ($scheme !== null) {
                $payment->setAdditionalInformation(
                    self::ADDITIONAL_INFO_FLOW_METHOD_ID,
                    FlowMethod::CODE . '_' . $scheme
                );
            }

            $this->quoteRepository->save($quote);

            $this->emit('checkoutcom_flow:scheme_selected', ['scheme' => $scheme]);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage(
                (string)__('We could not save your payment selection. Please try again.')
            );
            $this->logger->error(
                '[CheckoutCom Flow] Failed to persist selected scheme: ' . $exception->getMessage()
            );
        }

        return $scheme;
    }

    public function setSaveCard(bool $save): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
            $quote->setData(FlowGeneralSettings::SALES_ATTRIBUTE_SHOULD_SAVE_CARD, $save);
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->logger->error(
                '[CheckoutCom Flow] Failed to persist save-card preference: ' . $exception->getMessage()
            );

            return false;
        }

        return $save;
    }

    private function normalizeScheme(string|array|null $scheme): ?string
    {
        if (empty($scheme)) {
            return null;
        }

        if (is_string($scheme)) {
            return $scheme;
        }

        if (isset($scheme['scheme']) && is_string($scheme['scheme'])) {
            return $scheme['scheme'] ?: null;
        }

        if (isset($scheme['selectedType']) && is_string($scheme['selectedType'])) {
            return $scheme['selectedType'] ?: null;
        }

        foreach ($scheme as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
