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
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\DirectoryList;

class ReclaimTest extends TestCase
{
    /**
     * @var Reclaim
     */
    protected $reclaim;

    //path to our temporary test log file
    const TEST_LOG_PATH = '/var/www/html/var/log/klaviyo.test.log';

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

        $subscriberMock = $this->createMock(Subscriber::class);

        $subscriberCollectionMock = $this->getMockBuilder(SubscriberCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getVersion')->willReturn(SampleExtension::RECLAIM_VERSION);
        $scopeSettingMock->method('isLoggerEnabled')->willReturn(true);

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
            self::TEST_LOG_PATH
        );
        $loggerMock = new Logger(
            'Klaviyo',
            [$handlerMock]
        );
        $loggerHelperMock = new LoggerHelper(
            $directoryListMock,
            $loggerMock,
            $scopeSettingMock,
            self::TEST_LOG_PATH
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
            $subscriberMock,
            $subscriberCollectionMock,
            $scopeSettingMock,
            $loggerHelperMock
        );

        /**
         * create test log file with dummy entries
         */
        $testLogFile = fopen(self::TEST_LOG_PATH, 'wb');
        foreach (self::TEST_ENTRIES as $entry) {
            fwrite($testLogFile, $entry . "\r\n");
        }
        fclose($testLogFile);
    }

    protected function tearDown(): void
    {
        unlink(self::TEST_LOG_PATH);
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
        $testLog = file(self::TEST_LOG_PATH);
        $this->assertSame($testLog, $this->reclaim->getLog());

        /**
         * test unsuccessful retrieval scenarios
         */
        $expectedResponse = array (
            'message' => 'Unable to retrieve log file with error: file(' . self::TEST_LOG_PATH . '): failed to open stream: No such file or directory'
        );
        unlink(self::TEST_LOG_PATH);
        $this->assertSame($expectedResponse, $this->reclaim->getLog());

        $testLogFile = fopen(self::TEST_LOG_PATH, 'wb');
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
        $testLog = file(self::TEST_LOG_PATH);
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
        $testLog = file(self::TEST_LOG_PATH);
        $this->assertSame($testLog, $this->reclaim->getLog());
    }
}
