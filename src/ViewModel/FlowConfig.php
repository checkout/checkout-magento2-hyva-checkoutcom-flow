<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\ViewModel;

use CheckoutCom\Magento2\Gateway\Config\Config;
use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use CheckoutCom\Magento2\Provider\FlowMethodSettings;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

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
class FlowConfig extends AbstractViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly FlowMethodSettings $flowMethodSettings,
        private readonly FlowGeneralSettings $flowGeneralSettings,
        private readonly UrlInterface $urlBuilder,
        private readonly SaveCardConfig $saveCardViewModel,
        private readonly Config $config,
        private readonly FlowPaymentMethodSettings $flowPaymentMethodSettings,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
    ) {
        parent::__construct($scopeConfig, $storeManager);
    }

    public function isEnabled(): bool
    {
        return $this->flowMethodSettings->isAvailable($this->getWebsiteCode());
    }

    public function getPrepareUrl(): string
    {
        return $this->urlBuilder->getUrl(self::PREPARE_ROUTE);
    }

    public function getPlaceOrderUrl(): string
    {
        return $this->urlBuilder->getUrl(self::PLACE_FLOW_ORDER_ROUTE);
    }

    public function getFlowSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl(self::FLOW_SUBMIT_ROUTE);
    }

    public function getVerifyFlowOrderUrl(): string
    {
        return $this->urlBuilder->getUrl(self::VERIFY_FLOW_ORDER_ROUTE);
    }

    public function getFailOrderUrl(): string
    {
        return $this->urlBuilder->getUrl(self::FAIL_FLOW_ORDER_ROUTE);
    }

    public function isFramesEnabled(): bool
    {
        return $this->flowGeneralSettings->useFrames($this->getWebsiteCode());
    }

    public function isDebugEnabled(): bool
    {
        return $this->isFlagEnabled(self::CONFIG_DEBUG)
            && $this->isFlagEnabled(self::CONFIG_CONSOLE_LOGGING);
    }

    /**
     * Returns the Apple Pay merchant ID configured in admin.
     * Used by the frontend to call ApplePaySession.canMakePayments().
     */
    public function getApplePayMerchantId(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_APPLE_PAY_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreCode()
        );
    }

    /**
     * Whether Flow Apple Pay should be offered on all browsers (not just Safari).
     */
    public function isFlowApplePayOnAllBrowsers(): bool
    {
        return $this->isFlagEnabled(self::CONFIG_APPLE_PAY_FLOW_ALL_BROWSERS);
    }

    /**
     * Returns 'top' or 'hidden' for the Flow card component's displayCardholderName option.
     */
    public function getCardholderNameDisplay(): string
    {
        $value = (int)$this->scopeConfig->getValue(
            self::CONFIG_DISPLAY_CARDHOLDER,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreCode()
        );

        return $value === 0 ? 'hidden' : 'top';
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->saveCardViewModel->isCustomerLoggedIn();
    }

    public function isSaveCardEnabled(): bool
    {
        return $this->saveCardViewModel->isSaveCardEnabled();
    }

    /**
     * Flow SDK component types of the wallets rendered INSIDE the grouped Flow method
     * (enabled AND flow_standalone = No). Authoritative server-side decision from the core.
     *
     * @return string[]
     */
    public function getFlowInsideWallets(): array
    {
        return $this->config->getFlowInsideWallets();
    }

    /**
     * APM types enabled for Flow (payment/checkoutcom_apm/apm_flow_enabled) for the current website.
     *
     * @return string[]
     */
    public function getSelectedApmMethods(): array
    {
        return $this->flowPaymentMethodSettings->getSelectedApmMethods($this->getWebsiteCode());
    }

    /**
     * Ordered list of the sub-methods rendered inside the grouped Flow widget:
     * the card first, then the enabled Flow APMs, then the "inside" wallets.
     * Each SDK component is created individually from these types (parity with Luma 7.4).
     *
     * @return array<int, array{type: string, label: string}>
     */
    public function getGroupedMethods(): array
    {
        $types = array_merge(['card'], $this->getSelectedApmMethods(), $this->getFlowInsideWallets());

        $methods = [];
        foreach ($types as $type) {
            $type = (string)$type;
            if ($type === '') {
                continue;
            }
            $methods[] = ['type' => $type, 'label' => $this->getMethodLabel($type)];
        }

        return $methods;
    }

    /**
     * Human-readable label for a Flow sub-method type (parity with Luma apmLabel()).
     * Falls back to an ucfirst() of the raw type when no static label exists.
     */
    public function getMethodLabel(string $type): string
    {
        $labels = [
            'card' => (string)__('Credit / Debit Card'),
            'ideal' => 'iDEAL',
            'sepa' => (string)__('SEPA Direct Debit'),
            'eps' => 'EPS',
            'bancontact' => 'Bancontact',
            'knet' => 'KNET',
            'multibanco' => 'Multibanco',
            'klarna' => 'Klarna',
            'paypal' => 'PayPal',
            'googlepay' => 'Google Pay',
            'applepay' => 'Apple Pay',
            'bizum' => 'Bizum',
            'wechatpay' => 'WeChat Pay',
            'paynow' => 'PayNow',
            'octopus' => 'Octopus',
            'swish' => 'Swish',
            'blik' => 'BLIK',
        ];

        return $labels[$type] ?? ucfirst($type);
    }
}
