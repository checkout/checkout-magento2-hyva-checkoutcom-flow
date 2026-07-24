<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Plugin\Payment;

use Checkout\Payments\Product;
use Checkout\Payments\ProductFactory;
use Checkout\Payments\Sessions\PaymentSessionsRequest;
use CheckoutCom\Magento2\Model\Request\PostPaymentSessions;
use CheckoutCom\Magento2\Provider\CurrenciesSettings;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class NormalizePaymentSessionItems
{
    private const ADJUSTMENT_REFERENCE = 'CKO_ADJUST';

    /** APMs whose Checkout.com item schema requires `wxpay_goods_id` on each line. */
    private const GOODS_ID_METHODS = ['kakaopay', 'wechatpay'];

    public function __construct(
        private readonly CurrenciesSettings $currenciesSettings,
        private readonly ProductFactory $productFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param PostPaymentSessions $subject
     * @param PaymentSessionsRequest $result
     * @param CartInterface $quote
     * @param array $data
     *
     * @return PaymentSessionsRequest
     */
    public function afterGet(
        PostPaymentSessions $subject,
        PaymentSessionsRequest $result,
        CartInterface $quote,
        array $data
    ): PaymentSessionsRequest {
        try {
            $currency = (string)($result->currency ?? '');
            $items = is_array($result->items ?? null) ? $result->items : [];

            if ($currency !== '' && $this->isZeroDecimal($currency)) {
                $this->integeriseAmounts($result, $items);
            }

            if ($items !== [] && $this->needsGoodsId($result)) {
                foreach ($items as $item) {
                    if (empty($item->wxpay_goods_id)) {
                        $item->wxpay_goods_id = $this->buildGoodsId($item);
                    }
                }
                $result->items = $items;
            }
        } catch (Throwable $e) {
            // Never break checkout for a normalisation we could not apply — just log it.
            $this->logger->warning(
                sprintf('%s: unable to normalise payment session items: %s', __METHOD__, $e->getMessage())
            );
        }

        return $result;
    }

    /**
     * Re-integerise every amount for a zero-decimal currency, preserving
     * `total_amount == unit_price * quantity`, and realign the session amount with the
     * integerised items sum so Checkout.com's items/amount consistency check passes.
     *
     * @param PaymentSessionsRequest $result
     * @param Product[] $items (by reference: an adjustment line may be appended)
     */
    private function integeriseAmounts(PaymentSessionsRequest $result, array &$items): void
    {
        if ($items === []) {
            $result->amount = (int) round((float)($result->amount ?? 0));

            return;
        }

        $sum = 0;
        foreach ($items as $item) {
            $quantity = max(1, (int)($item->quantity ?? 1));
            $unitPrice = (int) round((float)($item->unit_price ?? 0));

            $item->quantity = $quantity;
            $item->unit_price = $unitPrice;
            // Adjustment line is a flat amount (qty 1); products/shipping follow unit * qty.
            $item->total_amount = ($item->reference ?? '') === self::ADJUSTMENT_REFERENCE
                ? $unitPrice
                : $unitPrice * $quantity;

            $sum += $item->total_amount;
        }

        // The grand total may differ from the integerised items sum by a sub-unit
        // remainder (rounding, or shipping excl/incl tax). Absorb it on the adjustment
        // line so amount == sum(items) and the charge stays equal to the items.
        $targetAmount = (int) round((float)($result->amount ?? 0));
        $diff = $targetAmount - $sum;
        if ($diff > 0) {
            $adjust = $this->findOrCreateAdjustment($items);
            $adjust->unit_price = (int)$adjust->unit_price + $diff;
            $adjust->total_amount = (int)$adjust->total_amount + $diff;
            $sum += $diff;
        }

        $result->amount = $sum;
        $result->items = $items;
    }

    private function isZeroDecimal(string $currency): bool
    {
        return in_array($currency, $this->currenciesSettings->getCurrenciesX1Table(), true);
    }

    private function needsGoodsId(PaymentSessionsRequest $result): bool
    {
        $enabled = $result->enabled_payment_methods ?? [];

        return is_array($enabled) && array_intersect($enabled, self::GOODS_ID_METHODS) !== [];
    }

    /**
     * @param Product[] $items (by reference)
     */
    private function findOrCreateAdjustment(array &$items): Product
    {
        foreach ($items as $item) {
            if (($item->reference ?? '') === self::ADJUSTMENT_REFERENCE) {
                return $item;
            }
        }

        /** @var Product $product */
        $product = $this->productFactory->create();
        $product->name = 'CheckoutCom rounding adjustment';
        $product->reference = self::ADJUSTMENT_REFERENCE;
        $product->quantity = 1;
        $product->unit_price = 0;
        $product->total_amount = 0;
        $items[] = $product;

        return $product;
    }

    private function buildGoodsId(Product $item): string
    {
        $reference = (string)($item->reference ?? '');
        if ($reference !== '') {
            return $reference;
        }

        $name = (string)($item->name ?? '');

        return $name !== '' ? $name : 'ITEM';
    }
}
