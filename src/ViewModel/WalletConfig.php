<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\ViewModel;

use CheckoutCom\Magento2\Provider\FlowPaymentMethodSettings;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

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
class WalletConfig extends AbstractViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly FlowPaymentMethodSettings $flowPaymentMethodSettings,
        private readonly UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
    )
    {
        parent::__construct($scopeConfig, $storeManager);
    }

    /**
     * Standalone wallets (flow_standalone = Yes) enabled for the current website,
     * as an ordered list of {code, type}. The SDK component type is passed through verbatim.
     *
     * @return array<int, array{code: string, type: string}>
     */
    public function getEnabledStandaloneWallets(): array
    {
        $website = $this->getWebsiteCode();
        $wallets = [];

        if ($this->flowPaymentMethodSettings->isGooglePayEnabled($website)
            && $this->flowPaymentMethodSettings->isGooglePayFlowStandalone($website)) {
            $wallets[] = ['code' => 'checkoutcom_flow_google_pay', 'type' => 'googlepay'];
        }

        if ($this->flowPaymentMethodSettings->isApplePayEnabled($website)
            && $this->flowPaymentMethodSettings->isApplePayFlowStandalone($website)
            && $this->flowPaymentMethodSettings->isApplePayEnabledOnCheckout($website)) {
            $wallets[] = ['code' => 'checkoutcom_flow_apple_pay', 'type' => 'applepay'];
        }

        if ($this->flowPaymentMethodSettings->isPaypalEnabled($website)
            && $this->flowPaymentMethodSettings->isPaypalFlowStandalone($website)) {
            $wallets[] = ['code' => 'checkoutcom_flow_paypal', 'type' => 'paypal'];
        }

        return $wallets;
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

    public function getApplePayMerchantId(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_APPLE_PAY_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE,
            $this->getStoreCode()
        );
    }

    public function isFlowApplePayOnAllBrowsers(): bool
    {
        return $this->isFlagEnabled(self::CONFIG_APPLE_PAY_FLOW_ALL_BROWSERS);
    }

    public function isDebugEnabled(): bool
    {
        return $this->isFlagEnabled(self::CONFIG_DEBUG);
    }
}
