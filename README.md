<img src="https://cdn.prod.website-files.com/64db80a5e88c6b1723ff760b/6788c0858b085eea2bcca244_Property%201%3Dweb-default.svg" alt="Checkout.com" width="200" />

# Checkout.com for Hyvä Checkout

Official compatibility module of the [Magento 2 Checkout.com module](https://github.com/checkout/checkout-magento2-plugin) for Hyvä Checkout.

> This package is required to use Checkout.com **inside Hyvä Checkout** (the checkout flow). To also use Checkout.com with the **Hyvä Theme outside of the checkout** (express buttons, PayPal review, account cards), install the complementary [`checkoutcom/module-magento-hyva`](https://github.com/checkout/module-magento-hyva) module **in addition**.

## About Checkout.com


[Checkout.com](https://www.checkout.com) is a software platform that has integrated 100% of the value chain to create payment infrastructures that truly make a difference. Checkout.com is authorized and regulated as a Payment institution by the UK Financial Conduct Authority.

The Checkout.com extension for Magento 2 allows shop owners to process online payments through the [Checkout.com Payment Gateway](https://docs.checkout.com/), including Card Payments with Frames.js, Apple Pay, Google Pay, Saved Cards, Instant Purchase, MOTO and a wide range of Alternative Payment Methods (Alipay, Bancontact, Boleto, EPS, Fawry, Ideal, Klarna, KNet, Poli, Sepa, Sofort, …).

Please refer to https://www.checkout.com for more information.

## Requirements

In order to work, this module requires:

- A valid [Hyvä Checkout](https://www.hyva.io/hyva-checkout.html) licence
- The base [`checkoutcom/magento2`](https://github.com/checkout/checkout-magento2-plugin) module installed and configured

| Dependency                              | Constraint               |
|-----------------------------------------|--------------------------|
| PHP                                     | 8.1 / 8.2 / 8.3 / 8.4    |
| `magento/framework`                     | `>=103.0.5`              |
| `hyva-themes/magento2-hyva-checkout`    | `^1.3.6 <1.4.0`          |
| `checkoutcom/magento2`                  | `*`                      |

Please refer to https://docs.hyva.io for more instructions about Hyvä Checkout prerequisites.

## Installation

1 - Install the module via composer:

```bash
composer require checkoutcom/module-magento-hyva-checkout
```

2 - Enable the module:

```bash
bin/magento module:enable CheckoutCom_Magento2HyvaCheckout
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

3 - Generate Tailwind CSS styles:

```bash
# Register module to Hyvä Tailwind config
bin/magento hyva:config:generate

# Replace path by the Hyvä default theme
cd vendor/hyva-themes/magento2-default-theme/web/tailwind/
# Or, if you use the Hyvä CSP-compliant default theme
cd vendor/hyva-themes/magento2-default-theme-csp/web/tailwind/
# Or replace path by your Hyvä custom theme if you have one
cd path/to/project/app/design/frontend/Vendor/default/web/tailwind

# Switch to the good Node.js version
nvm use

# Install dependencies
npm i

# Compile Tailwind CSS file
npm run build
```

## Configuration

This module does not add new configuration options. All Checkout.com settings remain managed from the base module under
**Stores > Configuration > Sales > Payment Methods > Checkout.com**.

In order to effectively process payments through the Checkout.com Payment Gateway, you will need to open an account.
Contact your [Checkout.com](https://www.checkout.com) account manager or send an email to support@checkout.com for more information.

## Support

Dedicated technical support is available to all merchants using Checkout.com via the public GitHub repositories or directly by email at integration@checkout.com. Checkout.com does not provide support for third-party plugins or any alterations made to the official Checkout.com plugins.

## License

MIT — see the `LICENSE` file at the repository root.

---

**DISCLAIMER**

In no event shall Checkout.com be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from the information or code provided or the use of the information or code provided. This disclaimer of liability refers to any technical issue or damage caused by the use or non-use of the information or code provided or by the use of incorrect or incomplete information or code provided.
