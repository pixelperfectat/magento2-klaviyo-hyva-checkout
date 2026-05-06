<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Magewire;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magewirephp\Magewire\Component;
use Psr\Log\LoggerInterface;

/**
 * Renders the "subscribe to email/SMS marketing" checkboxes on the payment
 * step (after the T&C block). Wire-bound state is persisted to the quote on
 * change so the kl_email_consent / kl_sms_consent attributes are present when
 * the order is placed and Klaviyo_Reclaim's SaveOrderMarketingConsent observer
 * runs.
 */
class MarketingConsent extends Component
{
    public bool $kl_email_consent = false;

    public bool $kl_sms_consent = false;

    public function __construct(
        private readonly ScopeSetting $scopeSetting,
        private readonly CheckoutSession $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
    }

    public function mount(): void
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $this->kl_email_consent = (bool) $quote->getData('kl_email_consent');
            $this->kl_sms_consent = (bool) $quote->getData('kl_sms_consent');
        } catch (\Exception $e) {
            $this->logger->error('Klaviyo MarketingConsent mount failed: ' . $e->getMessage());
        }
    }

    public function isEmailEnabled(): bool
    {
        return (bool) $this->scopeSetting->isEnabled()
            && (bool) $this->scopeSetting->getConsentAtCheckoutEmailIsActive();
    }

    public function isSmsEnabled(): bool
    {
        return (bool) $this->scopeSetting->isEnabled()
            && (bool) $this->scopeSetting->getConsentAtCheckoutSMSIsActive();
    }

    public function getEmailConsentText(): string
    {
        return (string) ($this->scopeSetting->getConsentAtCheckoutEmailText() ?: 'Subscribe for email updates');
    }

    public function getSmsLabelText(): string
    {
        return (string) ($this->scopeSetting->getConsentAtCheckoutSMSConsentLabelText() ?: 'Subscribe for SMS updates');
    }

    public function getSmsDisclosureText(): string
    {
        return (string) $this->scopeSetting->getConsentAtCheckoutSMSConsentText();
    }

    public function updatedKlEmailConsent($value): bool
    {
        $this->persistToQuote('kl_email_consent', (bool) $value);
        return (bool) $value;
    }

    public function updatedKlSmsConsent($value): bool
    {
        $this->persistToQuote('kl_sms_consent', (bool) $value);
        return (bool) $value;
    }

    private function persistToQuote(string $field, bool $value): void
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $quote->setData($field, $value ? 1 : 0);
            $quote->save();
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Klaviyo MarketingConsent persist %s failed: %s', $field, $e->getMessage()));
        }
    }
}
