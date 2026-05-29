<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

use Magento\Framework\Event\Observer;

/**
 * Stub observer that holds a stub event.
 */
class StubObserver extends Observer
{
    private $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function getEvent()
    {
        return $this->event;
    }
}
