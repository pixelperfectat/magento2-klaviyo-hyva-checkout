# Klaviyo Hyva Checkout Compatibility

Hyva Checkout (Magewire) compatibility module for [Klaviyo_Reclaim](https://github.com/klaviyo/magento2-klaviyo) (klaviyo/magento2-extension).

Based on [hyva-themes/magento2-hyva-checkout-klaviyo-reclaim](https://gitlab.hyva.io/hyva-themes/hyva-compat/magento2-hyva-checkout-klaviyo-reclaim) — implemented with Magewire form modifiers under the PixelPerfect namespace.

## Requirements

- Magento 2.4+
- Hyva Checkout (Magewire-based)
- hyva-themes/magento2-compat-module-fallback
- klaviyo/magento2-extension ^4.0

## Features

- Guest email capture for abandoned cart flows (via GuestDetailsForm modifier)
- SMS consent checkbox at checkout
- Email consent checkbox at checkout (all customers, not guest-only)
- Cart reload on checkout page load
- CSP strict compatible
