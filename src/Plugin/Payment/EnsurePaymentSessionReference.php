<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Plugin\Payment;

use CheckoutCom\Magento2\Model\Request\PaymentMethodAvailability\EnabledDisabledElement;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Ensures the payment session request carries a non-empty `reference` BEFORE the
 * availability filter runs.
 *
 * Checkout.com APMs flagged `referenceMandatory` in apm_flow.xml (benefit/BH, knet/KW,
 * and others) are removed by EnabledDisabledElement::checkMandatoriesFields() whenever
 * the request reference is empty. At the Flow "prepare" stage (payment session creation)
 * no order exists yet, so on checkoutcom/magento2 <= 7.3.0 PostPaymentSessions::get()
 * never sets $model->reference and these methods never reach the Flow widget.
 *
 * checkoutcom/magento2 7.4.0 fixes this upstream (PostPaymentSessions sets
 * $model->reference from QuoteHandlerService::getReference() before filtering). The
 * project is pinned to 7.3.0 max, hence this plugin reintroduces the same behaviour.
 *
 * Idempotent on purpose: it only acts when the reference is empty, so on a build that
 * already sets it (7.4.0) this is a no-op. Scoped to the frontend area (see
 * etc/frontend/di.xml) where the Flow prepare controller runs — covers both Luma and
 * Hyvä, and leaves the adminhtml pay-by-link flow (which sets its own reference) untouched.
 */
class EnsurePaymentSessionReference
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param EnabledDisabledElement $subject
     * @param mixed $payload PaymentSessionsRequest|PaymentLinkRequest
     * @param array $context
     *
     * @return void
     */
    public function beforeGet(EnabledDisabledElement $subject, $payload, array $context = []): void
    {
        try {
            if (!empty($payload->reference)) {
                return;
            }

            $reference = $this->resolveReference();
            if ($reference !== null && $reference !== '') {
                $payload->reference = $reference;
            }
        } catch (Throwable $e) {
            // Never break checkout for a reference we could not resolve — just log it.
            $this->logger->warning(
                sprintf('%s: unable to set payment session reference: %s', __METHOD__, $e->getMessage())
            );
        }
    }

    /**
     * Reserve (once) and return the quote's order id, used as the payment session reference.
     */
    private function resolveReference(): ?string
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote === null || !$quote->getId()) {
            return null;
        }

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId();
            $this->cartRepository->save($quote);
        }

        return $quote->getReservedOrderId();
    }
}
