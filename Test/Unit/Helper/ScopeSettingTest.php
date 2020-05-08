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

class ScopeSettingTest extends TestCase
{
    /**
     * @var ScopeSetting
     */
    protected $object;

    /**
     * used to toggle between list api endpoint preference in testing
     * @var boolean
     */
    protected $optinToggle;

    const RECLAIM_VERSION = '1.1.10';
    const IS_ENABLED = TRUE;
    const PUBLIC_API_KEY = 'QWEasd';
    const PRIVATE_API_KEY = 'pk_aaaassssddddffff';
    const NEW_API_KEY = 'pk_ffffddddssssaaaa';
    const USING_KLAVIYO_LOGGER = FALSE;
    const KLAVIYO_USERNAME = 'Klaviyo';
    const KLAVIYO_PASSWORD = 'password';
    const KLAVIYO_EMAIL = 'test@example.com';
    const CUSTOM_MEDIA_URL = 'https://www.example.com/';
    const NEWSLETTER = 'aaAAaa';

    protected function setUp()
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
                            return self::IS_ENABLED;
                            break;
                        case ScopeSetting::PUBLIC_API_KEY:
                            return self::PUBLIC_API_KEY;
                            break;
                        case ScopeSetting::PRIVATE_API_KEY:
                            return self::PRIVATE_API_KEY;
                            break;
                        case ScopeSetting::USING_KLAVIYO_LOGGER:
                            return self::USING_KLAVIYO_LOGGER;
                            break;
                        case ScopeSetting::KLAVIYO_USERNAME:
                            return self::KLAVIYO_USERNAME;
                            break;
                        case ScopeSetting::KLAVIYO_PASSWORD:
                            return self::KLAVIYO_PASSWORD;
                            break;
                        case ScopeSetting::KLAVIYO_EMAIL:
                            return self::KLAVIYO_EMAIL;
                            break;
                        case ScopeSetting::CUSTOM_MEDIA_URL:
                            return self::CUSTOM_MEDIA_URL;
                            break;
                        case ScopeSetting::NEWSLETTER:
                            return self::NEWSLETTER;
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
        $moduleListMock->method('getOne')->willReturn(array('setup_version'=>self::RECLAIM_VERSION));

        $configWriterMock = $this->createMock(WriterInterface::class);
        $configWriterMock->method('save')
        ->with($this->logicalOr(
            ScopeSetting::PRIVATE_API_KEY,
            ScopeSetting::KLAVIYO_USERNAME,
            ScopeSetting::KLAVIYO_PASSWORD,
            ScopeSetting::KLAVIYO_EMAIL
        ),
        $this->logicalOr(
            self::NEW_API_KEY,
            ScopeSetting::KLAVIYO_NAME_DEFAULT,
            ''
        ))
        ->will($this->returnCallback(
            function ($path, $value, $scope, $code) {
                switch ($path) {
                    case ScopeSetting::PRIVATE_API_KEY:
                        return ($value == self::NEW_API_KEY) ? $value : FALSE;
                        break;
                    case ScopeSetting::KLAVIYO_USERNAME:
                        return ($value == ScopeSetting::KLAVIYO_NAME_DEFAULT) ? $value : FALSE;
                        break;
                    case ScopeSetting::KLAVIYO_PASSWORD:
                        return ($value == '') ? $value : FALSE;
                        break;
                    case ScopeSetting::KLAVIYO_EMAIL:
                        return ($value == '') ? $value : FALSE;
                        break;
                }
            }
        ));

        $this->object = new ScopeSetting(
            $contextMock,
            $stateMock,
            $storeManagerMock,
            $moduleListMock,
            $configWriterMock
        );
    }

    public function testScopeSettingInstance()
    {
        $this->assertInstanceOf(ScopeSetting::class, $this->object);
    }

    public function testGetVersion()
    {
        $this->assertSame(self::RECLAIM_VERSION, $this->object->getVersion());
    }

    public function testIsEnabled()
    {
        $this->assertSame(self::IS_ENABLED, $this->object->isEnabled());
    }

    public function testGetPublicApiKey()
    {
        $this->assertSame(self::PUBLIC_API_KEY, $this->object->getPublicApiKey());
    }

    public function testGetPrivateApiKey()
    {
        $this->assertSame(self::PRIVATE_API_KEY, $this->object->getPrivateApiKey());
    }

    public function testSetPrivateApiKey()
    {
        $this->assertSame(self::NEW_API_KEY, $this->object->setPrivateApiKey(self::NEW_API_KEY));
    }

    public function testIsLoggerEnabled()
    {
        $this->assertSame(self::USING_KLAVIYO_LOGGER, $this->object->isLoggerEnabled());
    }

    public function testGetKlaviyoUsername()
    {
        $this->assertSame(self::KLAVIYO_USERNAME, $this->object->getKlaviyoUsername());
    }

    public function testUnsetKlaviyoUsername()
    {
        $this->assertSame(ScopeSetting::KLAVIYO_NAME_DEFAULT, $this->object->unsetKlaviyoUsername());
    }

    public function testGetKlaviyoPassword()
    {
        $this->assertSame(self::KLAVIYO_PASSWORD, $this->object->getKlaviyoPassword());
    }

    public function testUnsetKlaviyoPassword()
    {
        $this->assertSame('', $this->object->unsetKlaviyoPassword());
    }

    public function testGetKlaviyoEmail()
    {
        $this->assertSame(self::KLAVIYO_EMAIL, $this->object->getKlaviyoEmail());
    }

    public function testUnsetKlaviyoEmail()
    {
        $this->assertSame('', $this->object->unsetKlaviyoEmail());
    }

    public function testGetCustomMediaURL()
    {
        $this->assertSame(self::CUSTOM_MEDIA_URL, $this->object->getCustomMediaURL());
    }

    public function testGetNewsletter()
    {
        $this->assertSame(self::NEWSLETTER, $this->object->getNewsletter());
    }

    public function testGetOptInSetting()
    {
        $this->optinToggle = FALSE;
        $this->assertSame(ScopeSetting::API_MEMBERS, $this->object->getOptInSetting());
        $this->optinToggle = TRUE;
        $this->assertSame(ScopeSetting::API_SUBSCRIBE, $this->object->getOptInSetting());
    }

}