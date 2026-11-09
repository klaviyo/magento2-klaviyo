<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub quote item; qty is the line total and qtyToAdd the amount just added.
 */
class StubQuoteItem
{
    private $id;
    private $product;
    private $qty;
    private $qtyToAdd;
    private $productType;
    private $children;
    private $quote;

    public function __construct(
        $id,
        StubCartProduct $product,
        $qty,
        $qtyToAdd = null,
        $productType = 'simple',
        array $children = []
    ) {
        $this->id = $id;
        $this->product = $product;
        $this->qty = $qty;
        $this->qtyToAdd = $qtyToAdd;
        $this->productType = $productType;
        $this->children = $children;
    }

    public function setQuote($quote)
    {
        $this->quote = $quote;
        return $this;
    }

    public function getQuote()
    {
        return $this->quote;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getProductId()
    {
        return $this->product->getId();
    }

    public function getSku()
    {
        return $this->product->getSku();
    }

    public function getName()
    {
        return $this->product->getName();
    }

    public function getPrice()
    {
        return $this->product->getFinalPrice();
    }

    public function getQty()
    {
        return $this->qty;
    }

    public function getQtyToAdd()
    {
        return $this->qtyToAdd;
    }

    public function getProductType()
    {
        return $this->productType;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getParentItem()
    {
        return null;
    }

    public function getStoreId()
    {
        return 1;
    }
}
