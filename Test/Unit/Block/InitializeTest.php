<?php

namespace Klaviyo\Reclaim\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\Initialize;
use Magento\Framework\View\Element\Template\Context;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\Data\CustomerInterface;


class InitializeTest extends TestCase
{
    /**
     * @var Initialize
     */
    protected $object;

    protected function setUp()
    {
        $contextMock = $this->createMock(Context::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPublicApiKey')->willReturn('QWEasd');
        $scopeSettingMock->method('isEnabled')->willReturn(true);

        $customerDataMock = $this->createMock(CustomerInterface::class);
        $customerDataMock->method('getEmail')->willReturn('test@example.com');
        $customerDataMock->method('getFirstname')->willReturn('John');
        $customerDataMock->method('getLastname')->willReturn('Doe');

        $sessionMock = $this->createMock(Session::class);
        $sessionMock->method('isLoggedIn')->willReturn(true);
        $sessionMock->method('getCustomerData')->willReturn($customerDataMock);

        $this->object = new Initialize(
            $contextMock,
            $scopeSettingMock,
            $sessionMock
        );
    }

    public function testInitializeInstance()
    {
        $this->assertInstanceOf(Initialize::class, $this->object);
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame('QWEasd', $this->object->getPublicApiKey());
    }

    public function testIsKlaviyoEnabled()
    {
        $this->assertSame(true, $this->object->isKlaviyoEnabled());
    }

    public function testIsLoggedIn()
    {
        $this->assertSame(true, $this->object->isLoggedIn());
    }

    public function testGetCustomerEmail()
    {
        $this->assertSame('test@example.com', $this->object->getCustomerEmail());
    }

    public function testGetCustomerFirstname()
    {
        $this->assertSame('John', $this->object->getCustomerFirstname());
    }

    public function testGetCustomerLastname()
    {
        $this->assertSame('Doe', $this->object->getCustomerLastname());
    }
}