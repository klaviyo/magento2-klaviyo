<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub catalog product for Added to Cart payloads.
 */
class StubCartProduct
{
    private $id;
    private $sku;
    private $finalPrice;

    public function __construct($id, $sku, $finalPrice)
    {
        $this->id = $id;
        $this->sku = $sku;
        $this->finalPrice = $finalPrice;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function getName()
    {
        return "Product {$this->id}";
    }

    public function getFinalPrice()
    {
        return $this->finalPrice;
    }

    public function getCategoryIds()
    {
        return [];
    }

    public function getProductUrl()
    {
        return "https://example.com/product-{$this->id}";
    }

    public function getData($key = '')
    {
        return null;
    }
}
