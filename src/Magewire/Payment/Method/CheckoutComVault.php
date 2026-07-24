<?php

declare(strict_types=1);

namespace CheckoutCom\Magento2HyvaCheckout\Magewire\Payment\Method;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;

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
class CheckoutComVault extends Component
{
    public function __construct(
        private readonly SessionCheckout $sessionCheckout,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setSelectedCard(string $publicHash): void
    {
        try {
            $quote = $this->sessionCheckout->getQuote();
            $quote->getPayment()->setAdditionalInformation('public_hash', $publicHash);
            $this->quoteRepository->save($quote);
        } catch (LocalizedException $exception) {
            $this->dispatchErrorMessage(
                (string) __('We could not save your card selection. Please try again.')
            );
            $this->logger->error(
                '[CheckoutCom Vault] Failed to persist selected card: ' . $exception->getMessage()
            );
        }
    }
}
