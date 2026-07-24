<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
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
class SaveCardConfig extends AbstractViewModel implements ArgumentInterface
{
    public function __construct(
        private readonly CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
    ) {
        parent::__construct($scopeConfig, $storeManager);
    }

    public function isSaveCardEnabled(): bool
    {
        return $this->isCustomerLoggedIn()
            && $this->isFlagEnabled(self::CONFIG_SAVE_CARD);
    }

    public function isCustomerLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function isVaultEnabled(): bool
    {
        return $this->isFlagEnabled(self::VAULT_ENABLE);
    }
}
