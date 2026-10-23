<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Observer\SalesQuoteProductAddAfter;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubCartProduct;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubCartQuote;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubDataObserver;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubQuoteItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\CategoryFactory;

class SalesQuoteProductAddAfterTest extends TestCase
{
    /**
     * Runs the observer for the given added items and returns the payload it stores.
     */
    private function runObserver(array $addedItems, StubCartQuote $quote)
    {
        $dataHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $captured = null;
        $dataHelper->expects($this->once())
            ->method('setObserverAtcPayload')
            ->with($this->callback(function ($payload) use (&$captured) {
                $captured = $payload;
                return true;
            }));

        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $productRepository->method('getById')->willReturnCallback(function ($id) {
            return new StubCartProduct($id, "SKU-$id", 0);
        });
        $productRepository->method('get')->willReturnCallback(function ($sku) {
            return new StubCartProduct(99, $sku, 0);
        });

        $observer = new SalesQuoteProductAddAfter(
            $dataHelper,
            $this->createMock(CategoryFactory::class),
            $productRepository,
            new Image(),
            $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock()
        );
        $observer->execute(new StubDataObserver(['items' => $addedItems]));

        return $captured;
    }

    public function test_added_quantity_is_delta_when_merging_into_existing_line()
    {
        $item = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 5, 2);
        $quote = new StubCartQuote([$item], 73.53);

        $payload = $this->runObserver([$item], $quote);

        $this->assertSame(2, $payload['AddedItemQuantity']);
    }

    public function test_added_quantity_for_new_line()
    {
        $item = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 4, 4);
        $quote = new StubCartQuote([$item], 0);

        $payload = $this->runObserver([$item], $quote);

        $this->assertSame(4, $payload['AddedItemQuantity']);
    }

    public function test_added_quantity_falls_back_to_line_qty_when_qty_to_add_unset()
    {
        $item = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 3);
        $quote = new StubCartQuote([$item], 0);

        $payload = $this->runObserver([$item], $quote);

        $this->assertSame(3, $payload['AddedItemQuantity']);
    }

    public function test_cart_items_report_final_quantities()
    {
        $added = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 5, 2);
        $other = new StubQuoteItem(2, new StubCartProduct(20, 'SKU-20', 5.00), 1);
        $quote = new StubCartQuote([$added, $other], 73.53);

        $payload = $this->runObserver([$added], $quote);

        $this->assertSame([5, 1], array_column($payload['Items'], 'Quantity'));
        $this->assertSame(6, $payload['ItemCount']);
    }

    public function test_value_is_not_set_before_totals_are_collected()
    {
        $item = new StubQuoteItem(1, new StubCartProduct(10, 'SKU-10', 14.87), 5, 2);
        $quote = new StubCartQuote([$item], 73.53);

        $payload = $this->runObserver([$item], $quote);

        $this->assertArrayNotHasKey('$value', $payload);
    }

    public function test_bundle_reports_delta_and_bundle_options()
    {
        $child = new StubQuoteItem(3, new StubCartProduct(30, 'SKU-30', 0), 1, null, 'simple');
        $bundle = new StubQuoteItem(2, new StubCartProduct(20, 'SKU-20', 50.00), 3, 2, 'bundle', [$child]);
        $quote = new StubCartQuote([$bundle], 60.00);

        $payload = $this->runObserver([$bundle, $child], $quote);

        $this->assertSame(2, $payload['AddedItemQuantity']);
        $this->assertSame(
            [['Option Name' => 'Product 30', 'Option Qty' => 1]],
            $payload['AddedItemBundleOptions']
        );
    }
}
