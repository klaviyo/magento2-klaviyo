<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Checkout;

class ConsentQuote extends \Magento\Framework\DataObject
{
    public function setKlSmsConsent($consent)
    {
        return $this->setData('kl_sms_consent', $consent);
    }

    public function setKlEmailConsent($consent)
    {
        return $this->setData('kl_email_consent', $consent);
    }
}
