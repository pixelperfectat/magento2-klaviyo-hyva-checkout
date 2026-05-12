# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2026-05-12

### Fixed
- Marketing-consent block no longer throws Magewire's "Missing root tag"
  exception when both Email and SMS consent are disabled in admin. A new
  `Block\MarketingConsent` class gates the block at the layout/PHP layer:
  when neither consent surface is active it unsets the `magewire` argument
  before either Magewire view-block observer runs, so the block is invisible
  to Magewire's render lifecycle instead of producing empty markup that
  Magewire then fails to parse.

## [0.2.0] - 2026-05-11

### Added
- `Started Checkout` event for the `magento_two` integration. A server-side
  observer on the Hyvä checkout predispatch writes to `kl_events`; a dedicated
  cron in the existing `klaviyo_syncs` group drains `kl_sync` rows where
  `topic="Started Checkout"` via Klaviyo's private-key `/api/events/` endpoint,
  so the event registers under the `magento_two` integration tag and triggers
  Abandoned-Checkout flows. Per-session deduplication prevents reload spam.

### Changed
- Quote persistence in `EmailCaptureModifier` and `MarketingConsent` now uses
  `CartRepositoryInterface::save()` instead of the deprecated `Quote::save()`.
- `MarketingConsent::updatedKlEmailConsent` and `updatedKlSmsConsent` carry
  explicit `mixed` type hints to match Magewire's update-lifecycle contract.
- Removed the unused `UrlInterface` dependency from `EmailCaptureModifier`.

### Fixed
- Hyvä checkout layout targets the existing `main` container instead of the
  non-existent `content` container. The Klaviyo Initialize block (which
  carries the identify-on-email tracking and the new Started Checkout
  publisher) now actually mounts on the checkout page.

## [0.1.0] - 2026-04-30

### Added
- Initial Hyva Checkout (Magewire) compatibility for `Klaviyo_Reclaim`,
  including guest email capture into the quote and a Magewire-driven
  marketing-consent component rendered after the T&C block on the payment
  step.

[Unreleased]: https://github.com/pixelperfectat/magento2-klaviyo-hyva-checkout/compare/0.2.1...HEAD
[0.2.1]: https://github.com/pixelperfectat/magento2-klaviyo-hyva-checkout/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/pixelperfectat/magento2-klaviyo-hyva-checkout/compare/v0.1.0...0.2.0
[0.1.0]: https://github.com/pixelperfectat/magento2-klaviyo-hyva-checkout/releases/tag/v0.1.0
