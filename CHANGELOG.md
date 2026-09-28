# Changelog

All notable changes to `checkoutcom/module-magento-hyva-checkout` are documented in this file.

## [1.1.0] - 2026-09-28

### Added
- Terms & conditions check for Flow wallets. Apple Pay, Google Pay and PayPal no longer open their payment sheet until the shopper has ticked every terms & conditions checkbox that requires manual acceptance. This applies to the standalone wallet payment methods and to the wallets shown inside the grouped Checkout.com Flow method.
- When terms are missing, the standard Hyvä Checkout error is shown: the unaccepted agreements are highlighted and "Please accept all terms & conditions" appears.
- Hyvä compat module mapping of `CheckoutCom_Magento2` to `CheckoutCom_Magento2HyvaCheckout`.

### Unchanged
- Card payments still submit through the Hyvä place-order button, which already checks the terms.
- Stores with terms & conditions disabled, or configured with the "message" or "page" display types, are not affected.

## [1.0.0]

- Initial release of the Checkout.com Flow integration for Hyvä Checkout.
