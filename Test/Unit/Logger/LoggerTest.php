<?php

namespace Klaviyo\Reclaim\Test\Unit\Logger;

use Klaviyo\Reclaim\Logger\Logger;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testMessagesReachConfiguredHandlers()
    {
        $handler = new TestHandler();
        $logger = new Logger('Klaviyo', [$handler]);
        $logger->info('Sync completed', ['count' => 2]);
        $this->assertTrue($handler->hasInfoRecords());
        $records = $handler->getRecords();
        $this->assertSame('Sync completed', $records[0]['message']);
        $this->assertSame(['count' => 2], $records[0]['context']);
        $this->assertSame('Klaviyo', $records[0]['channel']);
    }
}
