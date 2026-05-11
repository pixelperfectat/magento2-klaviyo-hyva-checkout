<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Observer;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use PixelPerfect\KlaviyoHyvaCheckout\Service\StartedCheckoutPublisher;

/**
 * Fires once per checkout session when the visitor lands on the Hyvä checkout
 * page with a non-empty cart and an identified profile (email on quote OR
 * Klaviyo cookie present). Delegates the actual write + dedup to the
 * StartedCheckoutPublisher service.
 */
class PublishStartedCheckout implements ObserverInterface
{
    public function __construct(
        private readonly ScopeSetting $scopeSetting,
        private readonly CheckoutSession $checkoutSession,
        private readonly StartedCheckoutPublisher $publisher
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$this->scopeSetting->isEnabled()) {
            return;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            return;
        }

        $this->publisher->publishIfEligible($quote);
    }
}
