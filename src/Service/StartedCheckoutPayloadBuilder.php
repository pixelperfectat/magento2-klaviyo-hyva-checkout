<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Service;

use Klaviyo\Reclaim\Helper\Data as KlaviyoData;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Assembles the payload Klaviyo expects for the "Started Checkout" event,
 * mirroring the shape upstream Klaviyo_Reclaim builds for "Added To Cart"
 * (see SalesQuoteProductAddAfter::klBuildCartData) so flow templates and
 * segments stay consistent across both events.
 */
class StartedCheckoutPayloadBuilder
{
    public function __construct(
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly StoreManagerInterface $storeManager,
        private readonly KlaviyoData $klaviyoDataHelper
    ) {
    }

    /**
     * @return array<string, mixed>|null Null when the quote can't be the source of a Started Checkout event.
     */
    public function build(Quote $quote): ?array
    {
        if (!$quote->getId() || !$quote->getStoreId()) {
            return null;
        }

        $items = [];
        $itemNames = [];
        $categories = [];
        $itemCount = 0;

        /** @var QuoteItem $item */
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $itemCategories = $product ? (array) $product->getCategoryIds() : [];

            $items[] = [
                'Categories'  => $itemCategories,
                'ImageUrlKey' => $product ? (string) $product->getImage() : '',
                'ProductId'   => $product ? (int) $product->getId() : 0,
                'Sku'         => (string) $item->getSku(),
                'Price'       => $product ? (float) $product->getFinalPrice() : (float) $item->getPrice(),
                'Title'       => (string) $item->getName(),
                'Url'         => $product && $product->getProductUrl()
                    ? stripslashes((string) $product->getProductUrl())
                    : '',
                'Quantity'    => (int) $item->getQty(),
            ];
            $itemNames[] = (string) $item->getName();
            foreach ($itemCategories as $cat) {
                if (!in_array($cat, $categories, true)) {
                    $categories[] = $cat;
                }
            }
            $itemCount += (int) $item->getQty();
        }

        if ($itemCount === 0) {
            return null;
        }

        return [
            '$value'              => (float) $quote->getBaseGrandTotal(),
            '$event_id'           => sprintf(
                'started_checkout_%d_%d',
                (int) $quote->getId(),
                (int) strtotime((string) $quote->getUpdatedAt())
            ),
            'ItemNames'           => $itemNames,
            'Items'               => $items,
            'ItemCount'           => $itemCount,
            'Categories'          => $categories,
            'CheckoutURL'         => $this->buildCheckoutRestoreUrl($quote),
            'QuoteId'             => (string) $quote->getId(),
            'StoreId'             => (int) $quote->getStoreId(),
            'external_catalog_id' => (string) $this->klaviyoDataHelper->getExternalCatalogIdForEvent(
                (int) $quote->getStore()->getWebsiteId(),
                (int) $quote->getStoreId()
            ),
            'integration_key'     => 'magento_two',
            'time'                => time(),
        ];
    }

    private function buildCheckoutRestoreUrl(Quote $quote): string
    {
        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute((int) $quote->getId());
            $base = (string) $this->storeManager->getStore($quote->getStoreId())->getBaseUrl();
            return rtrim($base, '/') . '/checkout/cart/restore/' . $maskedId . '/';
        } catch (\Exception $e) {
            return (string) $this->storeManager->getStore($quote->getStoreId())->getBaseUrl() . 'checkout/';
        }
    }
}
