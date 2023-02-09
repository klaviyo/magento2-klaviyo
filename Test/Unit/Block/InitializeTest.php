<?php

namespace Klaviyo\Reclaim\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Klaviyo\Reclaim\Block\Initialize;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;

class InitializeTest extends TestCase
{
    /**
     * @var Initialize
     */
    protected $initialize;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn(SampleExtension::PUBLIC_API_KEY);
        $scopeSettingMock->method('isEnabled')->willReturn(SampleExtension::IS_ENABLED);

        $customerDataMock = $this->createMock(CustomerInterface::class);
        $customerDataMock->method('getEmail')->willReturn(SampleCustomer::CUSTOMER_EMAIL);
        $customerDataMock->method('getFirstname')->willReturn(SampleCustomer::CUSTOMER_FIRST_NAME);
        $customerDataMock->method('getLastname')->willReturn(SampleCustomer::CUSTOMER_LAST_NAME);

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('isLoggedIn')->willReturn(SampleCustomer::IS_LOGGED_IN);
        $sessionMock->method('getCustomerData')->willReturn($customerDataMock);

        $this->initialize = new Initialize(
            $contextMock,
            $scopeSettingMock,
            $sessionMock
        );
    }

    public function testInitializeInstance()
    {
        $this->assertInstanceOf(Initialize::class, $this->initialize);
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame(SampleExtension::PUBLIC_API_KEY, $this->initialize->getPublicApiKey());
    }

    public function testIsKlaviyoEnabled()
    {
        $this->assertSame(SampleExtension::IS_ENABLED, $this->initialize->isKlaviyoEnabled());
    }

    public function testIsLoggedIn()
    {
        $this->assertSame(SampleCustomer::IS_LOGGED_IN, $this->initialize->isLoggedIn());
    }

    public function testGetCustomerEmail()
    {
        $this->assertSame(SampleCustomer::CUSTOMER_EMAIL, $this->initialize->getCustomerEmail());
    }

    public function testGetCustomerFirstname()
    {
        $this->assertSame(SampleCustomer::CUSTOMER_FIRST_NAME, $this->initialize->getCustomerFirstname());
    }

    public function testGetCustomerLastname()
    {
        $this->assertSame(SampleCustomer::CUSTOMER_LAST_NAME, $this->initialize->getCustomerLastname());
    }
}
