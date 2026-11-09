<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer\Stubs;

use Magento\Framework\Event\Observer;

/**
 * Stub observer whose event data (e.g. 'items', 'quote') is read with getData().
 */
class StubDataObserver extends Observer
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData($key = '')
    {
        return $this->data[$key] ?? null;
    }
}
