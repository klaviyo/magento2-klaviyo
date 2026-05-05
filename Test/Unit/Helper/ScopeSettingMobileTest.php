<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ScopeSettingMobileTest extends TestCase
{
    protected function createScopeSettingWithConfig(array $configValues): ScopeSetting
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')->willReturn(1);

        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')
            ->will($this->returnCallback(
                function ($path) use ($configValues) {
                    return $configValues[$path] ?? null;
                }
            ));

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getScopeConfig')->willReturn($scopeConfigMock);
        $contextMock->method('getRequest')->willReturn($requestMock);

        $stateMock = $this->createMock(State::class);
        $stateMock->method('getAreaCode')->willReturn(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeManagerMock->method('getStore')->willReturn($storeMock);

        $moduleListMock = $this->createMock(ModuleListInterface::class);
        $moduleListMock->method('getOne')->willReturn(['setup_version' => '4.5.0']);

        $configWriterMock = $this->createMock(WriterInterface::class);

        return new ScopeSetting(
            $contextMock,
            $stateMock,
            $storeManagerMock,
            $moduleListMock,
            $configWriterMock
        );
    }

    public function test_getMobileConsentIsActive_returns_mocked_value()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([
            ScopeSetting::CONSENT_AT_CHECKOUT_MOBILE_IS_ACTIVE => '1',
        ]);
        $this->assertSame('1', $scopeSetting->getMobileConsentIsActive());
    }

    public function test_getMobileConsentChannels_returns_sms_array_when_config_returns_sms()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([
            ScopeSetting::CONSENT_AT_CHECKOUT_MOBILE_CHANNELS => 'sms',
        ]);
        $this->assertSame(['sms'], $scopeSetting->getMobileConsentChannels());
    }

    public function test_getMobileConsentChannels_returns_both_when_config_returns_sms_comma_whatsapp()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([
            ScopeSetting::CONSENT_AT_CHECKOUT_MOBILE_CHANNELS => 'sms,whatsapp',
        ]);
        $this->assertSame(['sms', 'whatsapp'], $scopeSetting->getMobileConsentChannels());
    }

    public function test_getMobileConsentChannels_returns_empty_array_when_config_returns_null()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([]);
        $this->assertSame([], $scopeSetting->getMobileConsentChannels());
    }

    public function test_isMobileChannelEnabled_returns_true_for_sms_when_channels_contains_sms()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([
            ScopeSetting::CONSENT_AT_CHECKOUT_MOBILE_CHANNELS => 'sms',
        ]);
        $this->assertTrue($scopeSetting->isMobileChannelEnabled(1, 'sms'));
    }

    public function test_isMobileChannelEnabled_returns_false_for_whatsapp_when_channels_contains_only_sms()
    {
        $scopeSetting = $this->createScopeSettingWithConfig([
            ScopeSetting::CONSENT_AT_CHECKOUT_MOBILE_CHANNELS => 'sms',
        ]);
        $this->assertFalse($scopeSetting->isMobileChannelEnabled(1, 'whatsapp'));
    }
}
