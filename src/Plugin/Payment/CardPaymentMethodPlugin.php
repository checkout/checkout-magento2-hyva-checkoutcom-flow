<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Plugin\Payment;

use CheckoutCom\Magento2\Model\Methods\CardPaymentMethod;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\CartInterface;

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
class CardPaymentMethodPlugin
{
    private const HYVA_CONTEXT_ACTION_PREFIXES = ['hyva_checkout_', 'magewire_'];

    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    public function afterIsAvailable(
        CardPaymentMethod $subject,
        bool $result,
        ?CartInterface $quote = null
    ): bool {
        if (!$result) {
            return false;
        }

        if ($this->isHyvaCheckoutRequest()) {
            return false;
        }

        return $result;
    }

    private function isHyvaCheckoutRequest(): bool
    {
        $actionName = (string)$this->request->getFullActionName();
        foreach (self::HYVA_CONTEXT_ACTION_PREFIXES as $prefix) {
            if (str_starts_with($actionName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
