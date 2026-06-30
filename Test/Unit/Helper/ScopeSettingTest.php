<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\State;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ScopeSettingTest extends TestCase
{
    /**
     * @var ScopeSetting
     */
    protected $scopeSetting;

    /**
     * used to toggle between list api endpoint preference in testing
     * @var boolean
     */
    protected $optinToggle;

    const NEW_API_KEY = 'pk_ffffddddssssaaaa';

    protected function setUp(): void
    {
        /**
         * mock scopesetting constructor arguments
         */
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')
            ->with($this->logicalOr(
                'store',
                'website'
            ))
            ->willReturn(1);
        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')
            ->will($this->returnCallback(
                function ($path, $scope, $code) {
                    switch ($path) {
                        case ScopeSetting::ENABLE:
                            return SampleExtension::IS_ENABLED;
                            break;
                        case ScopeSetting::PUBLIC_API_KEY:
                            return SampleExtension::PUBLIC_API_KEY;
                            break;
                        case ScopeSetting::PRIVATE_API_KEY:
                            return SampleExtension::PRIVATE_API_KEY;
                            break;
                        case ScopeSetting::USING_KLAVIYO_LOGGER:
                            return SampleExtension::USING_KLAVIYO_LOGGER;
                            break;
                        case ScopeSetting::CUSTOM_MEDIA_URL:
                            return SampleExtension::CUSTOM_MEDIA_URL;
                            break;
                        case ScopeSetting::NEWSLETTER:
                            return SampleExtension::NEWSLETTER;
                            break;
                        case ScopeSetting::USING_KLAVIYO_LIST_OPT_IN:
                            return $this->optinToggle;
                            break;
                    }
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
        $moduleListMock->method('getOne')->willReturn(['setup_version' => SampleExtension::RECLAIM_VERSION]);

        $configWriterMock = $this->createMock(WriterInterface::class);
        $configWriterMock->method('save')
            ->with(
                ScopeSetting::PRIVATE_API_KEY,
                self::NEW_API_KEY
            )
            ->will($this->returnCallback(
                function ($path, $value, $scope, $code) {
                    switch ($path) {
                        case ScopeSetting::PRIVATE_API_KEY:
                            return ($value == self::NEW_API_KEY) ? $value : false;
                            break;
                    }
                }
            ));

        $this->scopeSetting = new ScopeSetting(
            $contextMock,
            $stateMock,
            $storeManagerMock,
            $moduleListMock,
            $configWriterMock
        );
    }

    public function testScopeSettingInstance()
    {
        $this->assertInstanceOf(ScopeSetting::class, $this->scopeSetting);
    }

    public function testGetVersion()
    {
        $this->assertSame(SampleExtension::RECLAIM_VERSION, $this->scopeSetting->getVersion());
    }

    public function testIsEnabled()
    {
        $this->assertSame(SampleExtension::IS_ENABLED, $this->scopeSetting->isEnabled());
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame(SampleExtension::PUBLIC_API_KEY, $this->scopeSetting->getPublicApiKey());
    }

    public function testGetPrivateApiKey()
    {
        $this->assertSame(SampleExtension::PRIVATE_API_KEY, $this->scopeSetting->getPrivateApiKey());
    }

    public function testSetPrivateApiKey()
    {
        $this->assertSame(self::NEW_API_KEY, $this->scopeSetting->setPrivateApiKey(self::NEW_API_KEY));
    }

    public function testIsLoggerEnabled()
    {
        $this->assertSame(SampleExtension::USING_KLAVIYO_LOGGER, $this->scopeSetting->isLoggerEnabled());
    }

    public function testGetCustomMediaURL()
    {
        $this->assertSame(SampleExtension::CUSTOM_MEDIA_URL, $this->scopeSetting->getCustomMediaURL());
    }

    public function testGetNewsletter()
    {
        $this->assertSame(SampleExtension::NEWSLETTER, $this->scopeSetting->getNewsletter());
    }

    public function testGetOptInSetting()
    {
        $this->optinToggle = false;
        $this->assertSame(ScopeSetting::API_MEMBERS, $this->scopeSetting->getOptInSetting());
        $this->optinToggle = true;
        $this->assertSame(ScopeSetting::API_SUBSCRIBE, $this->scopeSetting->getOptInSetting());
    }

    public function testGetConsentAtCheckoutEmailSortOrderForwardsStoreId()
    {
        /**
         * Regression guard: the email sort-order getter must scope by the
         * requested store id. It previously dropped $storeId and returned the
         * default-scope value, which gave support misleading checkout settings
         * for multi-store setups.
         */
        $storeId = 42;

        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(
                ScopeSetting::CONSENT_AT_CHECKOUT_EMAIL_SORT_ORDER,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(7);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getScopeConfig')->willReturn($scopeConfigMock);
        $contextMock->method('getRequest')->willReturn($this->createMock(RequestInterface::class));

        $stateMock = $this->createMock(State::class);
        $stateMock->method('getAreaCode')
            ->willReturn(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(1);
        $storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $storeManagerMock->method('getStore')->willReturn($storeMock);

        $scopeSetting = new ScopeSetting(
            $contextMock,
            $stateMock,
            $storeManagerMock,
            $this->createMock(ModuleListInterface::class),
            $this->createMock(WriterInterface::class)
        );

        $this->assertSame(7, $scopeSetting->getConsentAtCheckoutEmailSortOrder($storeId));
    }
}
