<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Plugin\Payment;

use CheckoutCom\Magento2\Model\Methods\AbstractMethod;
use CheckoutCom\Magento2\Provider\FlowGeneralSettings;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * With the Flow SDK active, the individual Checkout.com methods (Klarna, APM/iDEAL,
 * Google Pay, Apple Pay, PayPal) are offered inside the Flow widget, not as standalone
 * Magento methods. They have no Hyvä renderer, so leaving them selectable lets a shopper
 * place an order that bypasses Flow — it stays "pending" and never reaches Checkout.com.
 * This plugin removes them from the Hyvä payment list while Flow is enabled. Flow and
 * Vault are left untouched as they have their own Hyvä renderers.
 *
 * Scope is intentionally limited to the Hyvä checkout request context (see
 * isHyvaCheckoutRequest): Luma already restricts methods at the Knockout renderer layer
 * (common/view/payment/method-selector.js), and decoupled/REST consumers
 * (e.g. GET /V1/carts/:id/payment-methods) are out of this module's scope.
 */
class HideNativeMethodWhenFlowPlugin
{
    private const HYVA_CONTEXT_ACTION_PREFIXES = ['hyva_checkout_', 'magewire_'];

    public function __construct(
        private readonly RequestInterface $request,
        private readonly FlowGeneralSettings $flowGeneralSettings,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterIsAvailable(
        AbstractMethod $subject,
        bool $result,
        ?CartInterface $quote = null
    ): bool {
        if (!$result) {
            return false;
        }

        if (!$this->isHyvaCheckoutRequest()) {
            return $result;
        }

        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
        } catch (Throwable $e) {
            $websiteCode = null;
            $this->logger->warning(
                sprintf(
                    '%s: unable to resolve website code, falling back to default scope: %s',
                    __METHOD__,
                    $e->getMessage()
                )
            );
        }

        if ($this->flowGeneralSettings->useFlow($websiteCode)) {
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
