<?php

namespace \Klaviyo\Reclaim\Test\Unit\Block;

use \PHPUnit\Framewok\TestCase;
use \Klaviyo\Reclaim\Block\Catalog\Product\ViewedProduct;

class ViewedProductTest extends TestCase
{
    /**
     * @var ViewedProduct
     */
    private $object;

    protected function setUp()
    {
        $this->object = new Initalize();
    }

    public function testInitalizeInstance()
    {
        $this->assertInstanceOf(ViewedProduct::class, $this->object);
    }

    public function testInitialize()
    {
        
    }
}