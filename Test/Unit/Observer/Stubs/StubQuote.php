<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub quote.
 */
class StubQuote
{
    private $mobileConsent;
    private $emailConsent;
    private $storeId;
    private $customerEmail;
    private $address;

    public function __construct($mobileConsent, $emailConsent, $storeId, $customerEmail, $address)
    {
        $this->mobileConsent = $mobileConsent;
        $this->emailConsent = $emailConsent;
        $this->storeId = $storeId;
        $this->customerEmail = $customerEmail;
        $this->address = $address;
    }

    public function getKlSmsConsent()
    {
        return $this->mobileConsent;
    }

    public function getKlEmailConsent()
    {
        return $this->emailConsent;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function getCustomerEmail()
    {
        return $this->customerEmail;
    }

    public function getShippingAddress()
    {
        return $this->address;
    }
}
