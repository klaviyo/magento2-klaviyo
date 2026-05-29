<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub event that holds order and quote.
 */
class StubEvent extends \Magento\Framework\Event\Event
{
    private $order;
    private $quote;

    public function __construct($order, $quote)
    {
        $this->order = $order;
        $this->quote = $quote;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getQuote()
    {
        return $this->quote;
    }
}
