<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

/**
 * Stub order that captures setData calls.
 */
class StubOrder
{
    public $data = [];

    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }
}
