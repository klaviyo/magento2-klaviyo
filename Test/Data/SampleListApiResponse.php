<?php

namespace Klaviyo\Reclaim\Test\Data;

class SampleListApiResponse
{
    /**
     * sample list API data to be used in tests
     */
    public $name;
    public $id;

    public function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
    }
}
