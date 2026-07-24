<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Block\Checkout\Payment\Method;

use CheckoutCom\Magento2HyvaCheckout\ViewModel\FlowConfig;
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
class FramesIncompatibilityNotice extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly FlowConfig $flowConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isVisible(): bool
    {
        return $this->flowConfig->isFramesEnabled();
    }

    protected function _toHtml(): string
    {
        if (!$this->isVisible()) {
            return '';
        }

        return parent::_toHtml();
    }
}
