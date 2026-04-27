<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Form\Consent;

use Hyva\Checkout\Magewire\Checkout\AddressView\AbstractMagewireAddressForm;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting;

class SmsConsentModifier implements EntityFormModifierInterface
{
    public function __construct(
        private ScopeSetting $scopeSetting
    ) {
    }

    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        if (!$this->scopeSetting->isEnabled()
            || !$this->scopeSetting->getConsentAtCheckoutSMSIsActive()
        ) {
            return $form;
        }

        $form->registerModificationListener(
            'PixelPerfect_KlaviyoHyvaCheckout::addSmsConsent',
            'form:build:magewire',
            [$this, 'addSmsConsentField']
        );

        return $form;
    }

    public function addSmsConsentField(EntityFormInterface $form, AbstractMagewireAddressForm $component): void
    {
        $consentText = $this->scopeSetting->getConsentAtCheckoutSMSConsentText() ?: 'Sign up for SMS';
        $sortOrder = (int) ($this->scopeSetting->getConsentAtCheckoutSMSConsentSortOrder() ?: 200);

        $field = $form->createField('kl_sms_consent', 'checkbox', [
            'data' => [
                'label' => $consentText,
                'is_required' => false,
                'sort_order' => $sortOrder,
            ]
        ]);

        $field->setAttribute('wire:model.defer', $field->getTracePath('data'));

        $form->addField($field);
    }
}
