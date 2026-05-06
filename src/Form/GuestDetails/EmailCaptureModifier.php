<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Form\GuestDetails;

use Hyva\Checkout\Magewire\Component\AbstractForm;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class EmailCaptureModifier implements EntityFormModifierInterface
{
    public function __construct(
        private ScopeSetting $scopeSetting,
        private CheckoutSession $checkoutSession,
        private UrlInterface $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        if (!$this->scopeSetting->isEnabled()) {
            return $form;
        }

        $form->registerModificationListener(
            'PixelPerfect_KlaviyoHyvaCheckout::captureEmail',
            'form:updated:magewire',
            [$this, 'captureEmail']
        );

        return $form;
    }

    /**
     * Magewire's form:updated:magewire hook fires for every property update on
     * the form component. It passes the changed property path (e.g.
     * "data.email_address") and new value as named arguments. We act only on
     * email_address changes, using the value Magewire just synced rather than
     * a $form->getField() lookup -- the form's field tree is not always
     * hydrated when this hook fires for individual property syncs.
     */
    public function captureEmail(
        EntityFormInterface $form,
        AbstractForm $component,
        ?string $property = null,
        mixed $value = null
    ): void {
        if ($property !== 'data.email_address' && $property !== 'email_address') {
            return;
        }

        if (!is_string($value) || $value === '' || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote->getCustomerEmail() === $value) {
                return;
            }

            $quote->setCustomerEmail($value);
            $quote->save();
        } catch (\Exception $e) {
            $this->logger->error('Klaviyo email capture failed: ' . $e->getMessage());
        }
    }
}
