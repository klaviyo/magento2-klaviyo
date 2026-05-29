<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Klaviyo\Reclaim\Observer\SaveOrderMarketingConsent;
use Klaviyo\Reclaim\Util\PhoneFormatter;

/**
 * Testable subclass that replaces buildKlaviyoV3Api() with an injected mock.
 */
class TestableSaveOrderMarketingConsent extends SaveOrderMarketingConsent
{
    private $apiMock;

    public function __construct(Logger $logger, ScopeSetting $scopeSetting, PhoneFormatter $phoneFormatter, KlaviyoV3Api $apiMock)
    {
        parent::__construct($logger, $scopeSetting, $phoneFormatter);
        $this->apiMock = $apiMock;
    }

    protected function buildKlaviyoV3Api($storeId = null): KlaviyoV3Api
    {
        return $this->apiMock;
    }
}
