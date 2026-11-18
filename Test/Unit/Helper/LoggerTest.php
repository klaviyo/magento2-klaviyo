<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Logger\Logger as KlaviyoLogger;
use Magento\Framework\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testDisabledLoggingDoesNotWriteAMessage()
    {
        $scope = $this->createMock(ScopeSetting::class);
        $scope->method('isLoggerEnabled')->willReturn(false);
        $logger = $this->createMock(KlaviyoLogger::class);
        $logger->expects($this->never())->method('log');
        $helper = new Logger($this->createMock(DirectoryList::class), $logger, $scope, '/tmp/klaviyo.log');
        $helper->log('message');
        $this->assertSame('/tmp/klaviyo.log', $helper->getPath());
    }
}
