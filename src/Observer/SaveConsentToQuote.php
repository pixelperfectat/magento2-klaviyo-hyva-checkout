<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;

class SaveConsentToQuote implements ObserverInterface
{
    public function __construct(
        private RequestInterface $request
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        if (!$quote) {
            return;
        }

        $params = $this->request->getParams();

        if (isset($params['kl_sms_consent'])) {
            $quote->setData('kl_sms_consent', (int) $params['kl_sms_consent']);
        }

        if (isset($params['kl_email_consent'])) {
            $quote->setData('kl_email_consent', (int) $params['kl_email_consent']);
        }
    }
}
