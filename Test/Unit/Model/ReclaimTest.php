<?php

namespace Klaviyo\Reclaim\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Model\Reclaim;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Logger as LoggerHelper;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Klaviyo\Reclaim\Logger\Logger;
use Klaviyo\Reclaim\Logger\Handler;

class ReclaimTest extends TestCase
{
    /**
     * @var Reclaim
     */
    protected $object;

    //path to our temprary test log file
    const TEST_LOG_PATH = '/var/www/html/var/log/klaviyo.test.log';

    //array of test log entries
    const TEST_ENTRIES = [
        '[2019-01-01 00:00:00] Klaviyo.INFO: old message [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 1 [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 2 [] []',
        '[2020-04-06 17:24:43] Klaviyo.INFO: test message 3 [] []'
    ];

    protected function setUp()
    {
        /**
         * Mock Reclaim constructor arguments
         */
        //should not be mocking this, Reclaim needs to be refactored to make a lot of it testable
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
        $scopeSettingMock->method('getVersion')->willReturn('1.1.9');
        $scopeSettingMock->method('isLoggerEnabled')->willReturn(TRUE);

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
            "Klaviyo",
            array($handlerMock)
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
        $this->object = new Reclaim(
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
        chmod(self::TEST_LOG_PATH, 0644);
        foreach (self::TEST_ENTRIES as $entry)
        {
            fwrite($testLogFile, $entry . "\r\n");
        }
        fclose($testLogFile);
    }

    protected function tearDown()
    {
        unlink(self::TEST_LOG_PATH);
    }

    public function testReclaimInstance()
    {
        $this->assertInstanceOf(Reclaim::class, $this->object);
    }

    public function testReclaim()
    {
        $this->assertSame('1.1.9', $this->object->reclaim());
    }

    public function testGetLog()
    {
        /**
         * test successful retrieval
         */
        $testLog = file(self::TEST_LOG_PATH);
        $this->assertSame($testLog, $this->object->getLog());

        /**
         * test unsuccessful retrieval scenarios
         */
        $expectedResponse = array (
            'message' => 'Unable to retrieve log file with error: file(' . self::TEST_LOG_PATH . '): failed to open stream: No such file or directory'
        );
        unlink(self::TEST_LOG_PATH);
        $this->assertSame($expectedResponse, $this->object->getLog());

        $testLogFile = fopen(self::TEST_LOG_PATH, 'wb');
        chmod(self::TEST_LOG_PATH, 0644);
        fclose($testLogFile);
        $expectedResponse = array (
            'message' => 'Log file is empty'
        );
        $this->assertSame($expectedResponse, $this->object->getLog());
    }

    public function testCleanLog()
    {
        /**
         * Test when invalid date is provided
         */
        $badDateString = 'asdf123asdfa';
        $expectedResponse = array(
            'message' => 'Unable to parse timestamp: ' . $badDateString
        );
        $this->assertSame($expectedResponse, $this->object->cleanLog($badDateString));

        /**
         * Test when valid date is provided
         */
        $validDateString = '2020-01-01 00:00:00';
        $expectedResponse = $response = array(
            'message' => 'Cleaned all log entries before: ' . $validDateString
        );
        $this->assertSame($expectedResponse, $this->object->cleanLog($validDateString));

        //checking side effects
        $testLog = file(self::TEST_LOG_PATH);
        $this->assertSame($testLog, $this->object->getLog());
    }

    public function testAppendLog()
    {
        $message = 'This is a test message';
        $expectedResponse = array(
            'message' => 'Logged message: \'' . $message . '\''
        );
        $this->assertSame($expectedResponse, $this->object->appendLog($message));

        //checking side effects
        $testLog = file(self::TEST_LOG_PATH);
        $this->assertSame($testLog, $this->object->getLog());
    }

    /**
     * These methods need to be refactored to remove ObjectManager.
     * As is, they are basically untestable as you cannot mock some
     * of the ObjectManager methods they use
     */
    // public function testStores()
    // {
        
    // }

    // public function testProduct()
    // {
        
    // }

    // public function testProductVariantInventory()
    // {

    // }

    // public function testProductinspector()
    // {

    // }

    // public function testGetSubscribersCount()
    // {

    // }

    // public function testGetSubscribersById()
    // {
        
    // }

    // public function testGetSubscribersByDateRange()
    // {
        
    // }

    /**
     * not sure if these need to be tested directly
     * they probably should not be public functions
     */
    // public function test_PackageSubscribers()
    // {
        
    // }

    // public function test_StoreFilter()
    // {
        
    // }

    // public function test_GetImages()
    // {
        
    // }

    // public function testHandleMediaURL()
    // {
        
    // }

    // public function test_GetStockItem()
    // {
        
    // }
}