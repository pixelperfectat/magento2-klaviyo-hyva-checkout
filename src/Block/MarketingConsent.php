<?php

declare(strict_types=1);

namespace PixelPerfect\KlaviyoHyvaCheckout\Block;

use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\View\Element\Template;

/**
 * Layout-level gate for the marketing-consent Magewire block.
 *
 * When the admin has not enabled at least one of the two consent surfaces
 * (email OR SMS), this block both skips its template render and unsets the
 * `magewire` argument before either of Magewire's view_block observers run.
 * Magewire's observers are no-ops on blocks without `magewire` data, so the
 * "Missing root tag" exception that would otherwise surface from an empty
 * render path never gets a chance to throw.
 *
 * Keeps the gate at the layout/PHP layer rather than in the .phtml template
 * so the template can stay a thin presenter.
 */
class MarketingConsent extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly ScopeSetting $scopeSetting,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        if (!$this->shouldRender()) {
            $this->unsetData('magewire');
            $this->setData('template', '');
        }
        return parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        if (!$this->shouldRender()) {
            return '';
        }
        return parent::_toHtml();
    }

    private function shouldRender(): bool
    {
        if (!$this->scopeSetting->isEnabled()) {
            return false;
        }
        return (bool) $this->scopeSetting->getConsentAtCheckoutEmailIsActive()
            || (bool) $this->scopeSetting->getConsentAtCheckoutSMSIsActive();
    }
}
