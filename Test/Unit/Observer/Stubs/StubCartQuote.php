<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub quote with cart items and totals for Added to Cart observers.
 */
class StubCartQuote
{
    private $items;
    private $baseGrandTotal;

    public function __construct(array $items, $baseGrandTotal)
    {
        $this->items = $items;
        $this->baseGrandTotal = $baseGrandTotal;
        foreach ($items as $item) {
            $item->setQuote($this);
        }
    }

    public function getAllVisibleItems()
    {
        return $this->items;
    }

    public function getBaseGrandTotal()
    {
        return $this->baseGrandTotal;
    }

    public function getId()
    {
        return 42;
    }

    public function getStoreId()
    {
        return 1;
    }

    public function getStore()
    {
        return new class {
            public function getWebsiteId()
            {
                return 1;
            }
        };
    }

    public function getCustomer()
    {
        return new class {
            public function getId()
            {
                return null;
            }
        };
    }
}
