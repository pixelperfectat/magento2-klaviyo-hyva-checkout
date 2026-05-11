<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Cron;

use Klaviyo\Reclaim\Helper\Data as KlaviyoData;
use Klaviyo\Reclaim\Model\ResourceModel\Syncs\CollectionFactory as SyncCollectionFactory;
use PixelPerfect\KlaviyoHyvaCheckout\Service\StartedCheckoutPublisher;
use Psr\Log\LoggerInterface;

/**
 * Drains kl_sync rows where topic = "Started Checkout" — upstream's
 * KlSyncs::sendUpdatesToApp hardcodes a topic allowlist that excludes us, so
 * its cron leaves our rows untouched. We pick them up, fire via
 * Data::klaviyoTrackEvent (which uses the private API key and so registers
 * the event under the magento_two integration), and update statuses.
 */
class SyncStartedCheckout
{
    public function __construct(
        private readonly SyncCollectionFactory $syncCollectionFactory,
        private readonly KlaviyoData $klaviyoDataHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $syncCollection = $this->syncCollectionFactory->create();
        $rows = $syncCollection->getRowsForSync('NEW')
            ->addFieldToFilter('topic', StartedCheckoutPublisher::EVENT_NAME)
            ->getData();

        if (empty($rows)) {
            return;
        }

        $synced = [];
        $failed = [];

        foreach ($rows as $row) {
            try {
                $payload = json_decode((string) $row['payload'], true);
                if (!is_array($payload)) {
                    $this->logger->warning(sprintf(
                        'Klaviyo Started Checkout drain: row %s has invalid payload, marking failed',
                        $row['id']
                    ));
                    $failed[] = $row['id'];
                    continue;
                }

                $userProperties = json_decode((string) $row['user_properties'], true);
                if (!is_array($userProperties)) {
                    $userProperties = [];
                }

                $eventTime = (int) ($payload['time'] ?? time());
                unset($payload['time']);
                $storeId = (int) ($payload['StoreId'] ?? 0);

                $response = $this->klaviyoDataHelper->klaviyoTrackEvent(
                    StartedCheckoutPublisher::EVENT_NAME,
                    $userProperties,
                    $payload,
                    $eventTime,
                    $storeId ?: null
                );

                if (is_array($response) && isset($response['errors'])) {
                    $failed[] = $row['id'];
                } else {
                    $synced[] = $row['id'];
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Klaviyo Started Checkout drain: row %s threw: %s',
                    $row['id'],
                    $e->getMessage()
                ));
                $failed[] = $row['id'];
            }
        }

        $statusUpdater = $this->syncCollectionFactory->create();
        $statusUpdater->updateRowStatus($synced, 'SYNCED');
        $statusUpdater->updateRowStatus($failed, 'RETRY');
    }
}
