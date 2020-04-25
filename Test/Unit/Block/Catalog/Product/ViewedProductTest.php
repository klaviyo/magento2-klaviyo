<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\Catalog\Product;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;

class ViewedProductTest extends TestCase
{
    /**
     * @var ViewedProduct
     */
    private $object;

    protected function setUp(): void
    {
        $this->object = new ViewedProduct();
    }

    public function testViewedProductInstance()
    {
        $this->assertInstanceOf(ViewedProduct::class, $this->object);
    }

    public function testViewedProduct()
    {
        
    }
}