<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Plugin\Payment;

use CheckoutCom\Magento2\Model\Methods\FlowMethod;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
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
class FlowMethodPlugin
{
    public function __construct(
        private readonly FlowGeneralSettings $flowGeneralSettings,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function afterIsAvailable(
        FlowMethod $subject,
        bool $result,
        ?CartInterface $quote = null
    ): bool {
        if (!$result) {
            return false;
        }

        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Throwable) {
            $websiteCode = null;
        }

        if ($this->flowGeneralSettings->useFrames($websiteCode)) {
            return false;
        }

        return $result;
    }
}
