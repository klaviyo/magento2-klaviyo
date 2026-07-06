<?php

namespace Klaviyo\Reclaim\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Model\Reclaim;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Logger as LoggerHelper;
use Klaviyo\Reclaim\Logger\Logger;
use Klaviyo\Reclaim\Logger\Handler;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class ReclaimTest extends TestCase
{
    /**
     * @var Reclaim
     */
    protected $reclaim;

    /**
     * @var ScopeSetting
     */
    protected $scopeSettingMock;

    /**
     * Path to our temporary test log file. Computed rather than a literal
     * constant so it resolves to a writable path regardless of environment
     * (CI runner, local host, or a Magento docker container).
     */
    private static function testLogPath(): string
    {
        return sys_get_temp_dir() . '/klaviyo.test.log';
    }

    //array of test log entries
    const TEST_ENTRIES = [
        '[2019-01-01 00:00:00] Klaviyo.INFO: old message [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 1 [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 2 [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 3 [] []'
    ];

    protected function setUp(): void
    {
        /**
         * Mock Reclaim constructor arguments
         */
        $objectManagerMock = $this->createMock(ObjectManagerInterface::class);

        $quoteFactoryMock = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productFactoryMock = $this->getMockBuilder(ProductFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stockItemMock = $this->createMock(StockStateInterface::class);

        $stockItemRepositoryMock = $this->createMock(StockItemRepository::class);

        $subscriberCollectionMock = $this->getMockBuilder(SubscriberCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getVersion')->willReturn(SampleExtension::RECLAIM_VERSION);
        $scopeSettingMock->method('isLoggerEnabled')->willReturn(true);
        $this->scopeSettingMock = $scopeSettingMock;

        /**
         * the logger and handler are linked and invoked using settings
         * found in etc/di.xml
         */
        $filesystemMock = $this->createMock(DriverInterface::class);
        $directoryListMock = $this->createMock(DirectoryList::class);
        $directoryListMock->method('getPath')->willReturn('');
        $handlerMock = new Handler(
            $filesystemMock,
            $directoryListMock,
            self::testLogPath()
        );
        $loggerMock = new Logger(
            'Klaviyo',
            [$handlerMock]
        );
        $loggerHelperMock = new LoggerHelper(
            $directoryListMock,
            $loggerMock,
            $scopeSettingMock,
            self::testLogPath()
        );

        /**
         * Create new Reclaim with mocked arguments
         */
        $this->reclaim = new Reclaim(
            $objectManagerMock,
            $quoteFactoryMock,
            $productFactoryMock,
            $stockItemMock,
            $stockItemRepositoryMock,
            $subscriberCollectionMock,
            $scopeSettingMock,
            $loggerHelperMock
        );

        /**
         * create test log file with dummy entries
         */
        $testLogFile = fopen(self::testLogPath(), 'wb');
        foreach (self::TEST_ENTRIES as $entry) {
            fwrite($testLogFile, $entry . "\r\n");
        }
        fclose($testLogFile);
    }

    protected function tearDown(): void
    {
        unlink(self::testLogPath());
    }

    public function testReclaimInstance()
    {
        $this->assertInstanceOf(Reclaim::class, $this->reclaim);
    }

    public function testReclaim()
    {
        $this->assertSame(SampleExtension::RECLAIM_VERSION, $this->reclaim->reclaim());
    }

    public function testGetLog()
    {
        /**
         * test successful retrieval
         */
        $testLog = file(self::testLogPath());
        $this->assertSame($testLog, $this->reclaim->getLog());

        /**
         * test unsuccessful retrieval scenarios
         *
         * PHP's own "failed to open stream" wording capitalizes differently
         * across versions, so compare case-insensitively rather than pinning
         * to one PHP version's exact wording.
         */
        $expectedMessage = 'Unable to retrieve log file with error: file(' . self::testLogPath() . '): failed to open stream: No such file or directory';
        unlink(self::testLogPath());
        $response = $this->reclaim->getLog();
        $this->assertSame(strtolower($expectedMessage), strtolower($response['message']));

        $testLogFile = fopen(self::testLogPath(), 'wb');
        fclose($testLogFile);
        $expectedResponse = array (
            'message' => 'Log file is empty'
        );
        $this->assertSame($expectedResponse, $this->reclaim->getLog());
    }

    public function testCleanLogInvalidDateInput()
    {
        /**
         * Test when invalid date is provided
         */
        $badDateString = 'asdf123asdfa';
        $expectedResponse = [
            'message' => 'Unable to parse timestamp: ' . $badDateString
        ];
        $this->assertSame($expectedResponse, $this->reclaim->cleanLog($badDateString));
    }

    public function testCleanLogValidDateInput()
    {
        /**
         * Test when valid date is provided
         */
        $validDateString = '2020-01-01 00:00:00';
        $expectedResponse = [
            'message' => 'Cleaned all log entries before: ' . $validDateString
        ];
        $this->assertSame($expectedResponse, $this->reclaim->cleanLog($validDateString));

        //checking side effects
        $testLog = file(self::testLogPath());
        $this->assertSame($testLog, $this->reclaim->getLog());
    }

    public function testAppendLog()
    {
        $message = 'This is a test message';
        $expectedResponse = [
            'message' => 'Logged message: \'' . $message . '\''
        ];
        $this->assertSame($expectedResponse, $this->reclaim->appendLog($message));

        //checking side effects
        $testLog = file(self::testLogPath());
        $this->assertSame($testLog, $this->reclaim->getLog());
    }

    public function testGetPluginSettings()
    {
        $this->scopeSettingMock->method('isEnabled')->willReturn(true);
        $this->scopeSettingMock->method('getPublicApiKey')->willReturn('PUB123');
        $this->scopeSettingMock->method('getUsingKlaviyoListOptIn')->willReturn(true);
        $this->scopeSettingMock->method('getMobileConsentChannels')
            ->willReturn(['sms', 'whatsapp']);
        // A present private key and an absent webhook secret exercise both
        // branches of the redaction logic.
        $this->scopeSettingMock->method('getPrivateApiKey')->willReturn('super-secret');
        $this->scopeSettingMock->method('getWebhookSecret')->willReturn('');

        $result = $this->reclaim->getPluginSettings(1);

        // Magento serializes a top-level associative array as a list, so the
        // settings blob is wrapped in a single-element array.
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $settings = $result[0];

        $this->assertTrue($settings['Enable Klaviyo Extension']);
        $this->assertSame('PUB123', $settings['Public Klaviyo API Key']);
        $this->assertTrue($settings['Use Klaviyo Opt-In Settings']);
        $this->assertSame(['sms', 'whatsapp'], $settings['SMS channels']);

        // Sensitive values are never returned verbatim.
        $this->assertSame('PRESENT', $settings['Private Klaviyo API Key']);
        $this->assertSame('NULL', $settings['Webhook Secret']);
    }

    public function testGetPluginSettingsRedactsEverySensitiveSetting()
    {
        // Guard against fail-open redaction: drive getPluginSettings with a REAL
        // ScopeSetting whose backing config returns a unique sentinel for every
        // path, then assert no SENSITIVE_SETTINGS value reaches the output. A future
        // sensitive field returned verbatim would surface its sentinel and fail here.
        $sentinel = function ($path) {
            return 'LEAKED::' . $path;
        };

        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')->willReturnCallback(
            function ($path, $scope = null, $code = null) use ($sentinel) {
                return $sentinel($path);
            }
        );

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

        $reclaim = new Reclaim(
            $this->createMock(ObjectManagerInterface::class),
            $this->getMockBuilder(QuoteFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ProductFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $this->createMock(StockStateInterface::class),
            $this->createMock(StockItemRepository::class),
            $this->createMock(Subscriber::class),
            $this->getMockBuilder(SubscriberCollectionFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $scopeSetting,
            $this->createMock(LoggerHelper::class)
        );

        $serialized = json_encode($reclaim->getPluginSettings(1));

        foreach (ScopeSetting::SENSITIVE_SETTINGS as $path) {
            $this->assertStringNotContainsString(
                $sentinel($path),
                $serialized,
                "Sensitive setting '$path' leaked its raw value in getPluginSettings output"
            );
        }
    }

    public function testGetPluginSettingsCallsEveryScopeSettingGetter()
    {
        // Guard against fail-open omissions: getPluginSettings() is a hand-written
        // array literal, so a new ScopeSetting getter can be added without ever
        // being wired in there -- the setting just silently never appears in the
        // endpoint response, with nothing failing. Reflect over ScopeSetting's
        // public setting getters and assert getPluginSettings() calls each one.
        //
        // Two getters are intentionally excluded because they aren't plain setting
        // readers:
        // - getVersion(): module version, not a plugin setting.
        // - getOptInSetting(): derives an API endpoint fragment from
        //   USING_KLAVIYO_LIST_OPT_IN, which is already exposed verbatim via
        //   getUsingKlaviyoListOptIn().
        $excludedGetters = ['getVersion', 'getOptInSetting'];

        $reflection = new \ReflectionClass(ScopeSetting::class);
        $getterNames = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== ScopeSetting::class) {
                continue;
            }
            if (!preg_match('/^(get|is)/', $method->getName())) {
                continue;
            }
            if (in_array($method->getName(), $excludedGetters, true)) {
                continue;
            }

            $params = $method->getParameters();
            // Keep only plain setting getters: no args, or a single optional
            // (defaulted) $storeId. This drops setters, and helpers like
            // isMobileChannelEnabled($storeId, $channel) or
            // getStoreIdKlaviyoAccountSetMap($storeIds) that require real
            // arguments and aren't single-setting readers.
            if (count($params) > 1) {
                continue;
            }
            if (count($params) === 1 && !$params[0]->isDefaultValueAvailable()) {
                continue;
            }

            $getterNames[] = $method->getName();
        }

        $this->assertNotEmpty($getterNames, 'Expected to find setting getters on ScopeSetting');

        $scopeSettingMock = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->onlyMethods($getterNames)
            ->getMock();

        foreach ($getterNames as $name) {
            $scopeSettingMock->expects($this->atLeastOnce())->method($name)->willReturn(null);
        }

        $reclaim = new Reclaim(
            $this->createMock(ObjectManagerInterface::class),
            $this->getMockBuilder(QuoteFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ProductFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $this->createMock(StockStateInterface::class),
            $this->createMock(StockItemRepository::class),
            $this->getMockBuilder(SubscriberCollectionFactory::class)
                ->disableOriginalConstructor()->getMock(),
            $scopeSettingMock,
            $this->createMock(LoggerHelper::class)
        );

        // PHPUnit verifies the "atLeastOnce" expectations above when the mock is
        // torn down; a getter that getPluginSettings() never calls fails the test.
        $reclaim->getPluginSettings(1);
    }
}
