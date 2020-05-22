<?php

namespace Klaviyo\Reclaim\Test\Fakes;

use Magento\Framework\Event\Observer;

class ObserverFake extends Observer
{
    /**
     * this fake class is to allow for the use of a virtual method
     * which cannot be used otherwise
     */
    public function getDataObject()
    {
        return;
    }
}
