<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
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
class AbstractViewModel
{
    protected const CONFIG_APPLE_PAY_FLOW_ALL_BROWSERS = 'payment/checkoutcom_apple_pay/flow_enabled_on_all_browsers';
    protected const CONFIG_APPLE_PAY_MERCHANT_ID = 'payment/checkoutcom_apple_pay/merchant_id';
    protected const CONFIG_CONSOLE_LOGGING = 'settings/checkoutcom_configuration/console_logging';
    protected const CONFIG_DEBUG = 'settings/checkoutcom_configuration/debug';
    protected const CONFIG_DISPLAY_CARDHOLDER = 'payment/checkoutcom_card_payment/display_cardholder_name';
    protected const CONFIG_SAVE_CARD = 'payment/checkoutcom_card_payment/save_card_option';
    protected const FAIL_FLOW_ORDER_ROUTE = 'checkout_com/payment/failfloworder';
    protected const FLOW_SAVE_CARD_ROUTE = 'checkout_com/flow/saveCard';
    protected const FLOW_SUBMIT_ROUTE = 'checkout_com/flow/submit';
    protected const PLACE_FLOW_ORDER_ROUTE = 'checkout_com/payment/placefloworder';
    protected const PREPARE_ROUTE = 'checkout_com/flow/prepare';
    protected const SUCCESS_PAGE_ROUTE = 'checkout/onepage/success';
    protected const VAULT_ENABLE = 'payment/checkoutcom_vault/active';
    protected const VERIFY_FLOW_ORDER_ROUTE = 'checkout_com/payment/verifyfloworder';

    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly StoreManagerInterface $storeManager,
    ) {
    }

    protected function isFlagEnabled(string $path): bool
    {
        return (bool)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $this->getStoreCode());
    }

    protected function getStoreCode(): ?string
    {
        try {
            return $this->storeManager->getStore()->getCode();
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    protected function getWebsiteCode(): ?string
    {
        try {
            return $this->storeManager->getWebsite()->getCode();
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
