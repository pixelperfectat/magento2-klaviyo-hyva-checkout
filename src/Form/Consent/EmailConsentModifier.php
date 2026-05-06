<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Form\Consent;

use Hyva\Checkout\Magewire\Component\AbstractForm;
use Hyva\Checkout\Model\Form\EntityFormInterface;
use Hyva\Checkout\Model\Form\EntityFormModifierInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting;

class EmailConsentModifier implements EntityFormModifierInterface
{
    public function __construct(
        private ScopeSetting $scopeSetting
    ) {
    }

    public function apply(EntityFormInterface $form): EntityFormInterface
    {
        if (!$this->scopeSetting->isEnabled()
            || !$this->scopeSetting->getConsentAtCheckoutEmailIsActive()
        ) {
            return $form;
        }

        $form->registerModificationListener(
            'PixelPerfect_KlaviyoHyvaCheckout::addEmailConsent',
            'form:build:magewire',
            [$this, 'addEmailConsentField']
        );

        return $form;
    }

    public function addEmailConsentField(EntityFormInterface $form, AbstractForm $component): void
    {
        $consentText = $this->scopeSetting->getConsentAtCheckoutEmailText() ?: 'Sign up for email marketing';
        $sortOrder = (int) ($this->scopeSetting->getConsentAtCheckoutEmailSortOrder() ?: 210);

        $field = $form->createField('kl_email_consent', 'checkbox', [
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
