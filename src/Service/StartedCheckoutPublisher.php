<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Service;

use Klaviyo\Reclaim\Model\Events as KlaviyoEvents;
use Klaviyo\Reclaim\Model\EventsFactory as KlaviyoEventsFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Inserts a Started Checkout row into kl_events for the configured drain cron
 * to pick up. Klaviyo dedupes server-side via $event_id; we additionally dedupe
 * per-session here to avoid spamming kl_events on every checkout page reload.
 *
 * Topic = "Started Checkout" — not in upstream KlSyncs::sendUpdatesToApp's
 * hardcoded $trackApiTopics list, so the upstream cron will leave our rows
 * alone and SyncStartedCheckout drains them via Data::klaviyoTrackEvent which
 * authenticates with the private API key (and so lands the event under the
 * magento_two integration in Klaviyo's metric grouping).
 */
class StartedCheckoutPublisher
{
    public const EVENT_NAME = 'Started Checkout';
    private const SESSION_FLAG_PREFIX = 'kl_started_checkout_fired_';

    public function __construct(
        private readonly StartedCheckoutPayloadBuilder $payloadBuilder,
        private readonly KlaviyoEventsFactory $eventsFactory,
        private readonly CustomerSession $customerSession,
        private readonly LoggerInterface $logger
    ) {
    }

    public function publishIfEligible(Quote $quote): bool
    {
        if (!$quote->getId()) {
            return false;
        }
        if ($this->alreadyFiredInSession((int) $quote->getId())) {
            return false;
        }

        $userProperties = $this->buildUserProperties($quote);
        if ($userProperties === null) {
            return false;
        }

        $payload = $this->payloadBuilder->build($quote);
        if ($payload === null) {
            return false;
        }

        try {
            /** @var KlaviyoEvents $event */
            $event = $this->eventsFactory->create();
            $event->setData([
                'status'          => 'NEW',
                'user_properties' => json_encode($userProperties),
                'event'           => self::EVENT_NAME,
                'payload'         => json_encode($payload),
            ]);
            // Upstream Klaviyo_Reclaim doesn't expose a repository for kl_events;
            // SalesQuoteSaveAfter persists the same way.
            /** @phpstan-ignore-next-line magento.unnecessaryCollectionLoad,argument.type */
            $event->save();

            $this->markFiredInSession((int) $quote->getId());
            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Klaviyo Started Checkout publish failed for quote %d: %s',
                (int) $quote->getId(),
                $e->getMessage()
            ));
            return false;
        }
    }

    /**
     * Mirrors the identifier-extraction in upstream SalesQuoteSaveAfter so the
     * resulting event ties to the same Klaviyo profile.
     *
     * @return array<string, string>|null
     */
    private function buildUserProperties(Quote $quote): ?array
    {
        $cookie = $_COOKIE['__kla_id'] ?? null;
        if (is_string($cookie) && $cookie !== '') {
            $decoded = json_decode((string) base64_decode($cookie), true);
            if (is_array($decoded)) {
                if (!empty($decoded['$exchange_id'])) {
                    return ['$exchange_id' => (string) $decoded['$exchange_id']];
                }
                if (!empty($decoded['$email'])) {
                    return ['$email' => (string) $decoded['$email']];
                }
            }
        }

        $email = (string) $quote->getCustomerEmail();
        if ($email !== '') {
            return ['$email' => $email];
        }

        return null;
    }

    private function alreadyFiredInSession(int $quoteId): bool
    {
        return (bool) $this->customerSession->getData(self::SESSION_FLAG_PREFIX . $quoteId);
    }

    private function markFiredInSession(int $quoteId): void
    {
        $this->customerSession->setData(self::SESSION_FLAG_PREFIX . $quoteId, true);
    }
}
