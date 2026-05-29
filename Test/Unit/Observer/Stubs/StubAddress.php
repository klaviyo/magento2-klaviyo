<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub shipping address.
 */
class StubAddress
{
    private $phone;
    private $countryId;

    public function __construct($phone, $countryId = 'US')
    {
        $this->phone = $phone;
        $this->countryId = $countryId;
    }

    public function getTelephone()
    {
        return $this->phone;
    }

    public function getCountryId()
    {
        return $this->countryId;
    }
}
