<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Block\Checkout\Payment\Method;

use CheckoutCom\Magento2\Model\Service\CardHandlerService;
use CheckoutCom\Magento2\Model\Service\VaultHandlerService;
use CheckoutCom\Magento2HyvaCheckout\ViewModel\SaveCardConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;

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
class Vault extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly VaultHandlerService $vaultHandler,
        private readonly CardHandlerService $cardHandler,
        private readonly CustomerSession $customerSession,
        private readonly SaveCardConfig $saveCardConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCards(): array
    {
        if (!$this->saveCardConfig->isVaultEnabled() || !$this->customerSession->isLoggedIn()) {
            return [];
        }

        $cards = [];

        foreach ($this->vaultHandler->getUserCards() as $card) {
            $details = json_decode($card->getTokenDetails() ?: '{}', true);
            $typeCode = (string)($details['type'] ?? '');

            $cards[] = [
                'publicHash' => $card->getPublicHash(),
                'last4' => (string)($details['maskedCC'] ?? ''),
                'expiry' => (string)($details['expirationDate'] ?? ''),
                'brand' => (string)($this->cardHandler->getCardScheme($typeCode) ?? $typeCode),
            ];
        }

        return $cards;
    }

    protected function _toHtml(): string
    {
        if (!$this->saveCardConfig->isVaultEnabled() || !$this->customerSession->isLoggedIn()) {
            return '';
        }

        return parent::_toHtml();
    }
}
