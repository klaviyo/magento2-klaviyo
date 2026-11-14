<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Logger\Logger as KlaviyoLogger;
use Magento\Framework\Filesystem\DirectoryList;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\App\Helper\Context;

class DataTest extends TestCase
{
    public function testUnsubscribeResolvesListAndApiForGivenStore()
    {
        $scopeSetting = $this->createMock(ScopeSetting::class);
        $scopeSetting->expects($this->once())
            ->method('getNewsletter')
            ->with(7)
            ->willReturn('LIST_B');

        $api = $this->createMock(KlaviyoV3Api::class);
        $api->expects($this->once())
            ->method('unsubscribeEmailFromKlaviyoList')
            ->with('guest@example.com', 'LIST_B')
            ->willReturn(true);

        $helper = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->createMock(Logger::class),
                $scopeSetting
            ])
            ->onlyMethods(['buildKlaviyoV3Api'])
            ->getMock();
        $helper->expects($this->once())
            ->method('buildKlaviyoV3Api')
            ->with(7)
            ->willReturn($api);

        $this->assertTrue($helper->unsubscribeEmailFromKlaviyoList('guest@example.com', 7));
    }

    public function testSubscribeResolvesListAndOptInForGivenStore()
    {
        $scopeSetting = $this->createMock(ScopeSetting::class);
        $scopeSetting->expects($this->once())->method('getNewsletter')->with(7)->willReturn('LIST_B');
        $scopeSetting->expects($this->once())->method('getOptInSetting')->with(7)
            ->willReturn(ScopeSetting::API_SUBSCRIBE);

        $api = $this->createMock(KlaviyoV3Api::class);
        $api->expects($this->once())->method('subscribeMembersToList')->with('LIST_B');

        $helper = $this->getMockBuilder(Data::class)
            ->setConstructorArgs([
                $this->createMock(Context::class),
                $this->createMock(Logger::class),
                $scopeSetting
            ])
            ->onlyMethods(['buildKlaviyoV3Api'])
            ->getMock();
        $helper->expects($this->once())
            ->method('buildKlaviyoV3Api')
            ->with(7)
            ->willReturn($api);

        $helper->subscribeEmailToKlaviyoList('guest@example.com', null, null, 7);
    }
}
