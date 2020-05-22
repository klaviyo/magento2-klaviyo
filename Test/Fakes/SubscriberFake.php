<?php

namespace Klaviyo\Reclaim\Test\Fakes;

use Magento\Newsletter\Model\Subscriber;

class SubscriberFake extends Subscriber
{
    /**
     * this fake class is to allow for the use of a virtual method
     * which cannot be used otherwise
     */
    public function getCustomerId()
    {
        return;
    }
}
