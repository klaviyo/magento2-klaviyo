<?php

namespace Klaviyo\Reclaim\Test\Unit\Model;

class Subscriber
{
    public function getEmail()
    {
        return 'subscriber@example.com';
    }

    public function getSubscriberStatus()
    {
        return 1;
    }

    public function getChangeStatusAt()
    {
        return '2024-06-15 12:34:56';
    }
}
