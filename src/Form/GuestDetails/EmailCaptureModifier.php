<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Form\GuestDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\AbstractMagewireAddressForm;
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

    public function captureEmail(EntityFormInterface $form, AbstractMagewireAddressForm $component): void
    {
        $emailField = $form->getField('email_address');
        if ($emailField === null) {
            return;
        }

        $email = $emailField->getValue();
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote->getCustomerEmail() === $email) {
                return;
            }

            $quote->setCustomerEmail($email);
            $quote->save();
        } catch (\Exception $e) {
            $this->logger->error('Klaviyo email capture failed: ' . $e->getMessage());
        }
    }
}
