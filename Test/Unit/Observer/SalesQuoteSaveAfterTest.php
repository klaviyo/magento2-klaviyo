<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Model\Events;
use Klaviyo\Reclaim\Observer\SalesQuoteSaveAfter;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubCartProduct;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubCartQuote;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubDataObserver;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubQuoteItem;
use Magento\Customer\Model\Session;

class SalesQuoteSaveAfterTest extends TestCase
{
    protected function setUp(): void
    {
        $_COOKIE['__kla_id'] = base64_encode(json_encode(['$exchange_id' => 'abc123']));
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['__kla_id']);
    }

    public function test_value_is_post_add_base_grand_total()
    {
        $dataHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dataHelper->method('getObserverAtcPayload')->willReturn(['AddedItemQuantity' => 2]);
        $dataHelper->method('getExternalCatalogIdForEvent')->willReturn('1-1');
        $dataHelper->expects($this->once())->method('unsetObserverAtcPayload');

        $scopeSetting = $this->getMockBuilder(ScopeSetting::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeSetting->method('getPrivateApiKey')->willReturn('sk');

        $events = $this->getMockBuilder(Events::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'save'])
            ->getMock();
        $captured = null;
        $events->expects($this->once())
            ->method('setData')
            ->with($this->callback(function ($row) use (&$captured) {
                $captured = $row;
                return true;
            }))
            ->willReturnSelf();
        $events->expects($this->once())->method('save');

        $observer = new SalesQuoteSaveAfter(
            $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock(),
            $scopeSetting,
            $dataHelper,
            $events,
            $this->createMock(Session::class)
        );

        $item = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 5, 2);
        $quote = new StubCartQuote([$item], 109.22);
        $observer->execute(new StubDataObserver(['quote' => $quote]));

        $payload = json_decode($captured['payload'], true);
        $this->assertSame('Added To Cart', $captured['event']);
        $this->assertSame(109.22, $payload['$value']);
        $this->assertSame(2, $payload['AddedItemQuantity']);
        $this->assertSame(42, $payload['QuoteId']);
        $this->assertSame(1, $payload['StoreId']);
        $this->assertSame('1-1', $payload['external_catalog_id']);
        $this->assertSame('magento_two', $payload['integration_key']);
    }
}
