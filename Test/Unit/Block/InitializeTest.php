<?php

namespace Klaviyo\Reclaim\Test\Unit\Block;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\Initialize;

class InitializeTest extends TestCase
{
    /**
     * @var Initialize
     */
    private $object;

    protected function setUp(): void
    {
        $this->object = new Initialize();
    }

    public function testInitializeInstance()
    {
        $this->assertInstanceOf(Initialize::class, $this->object);
    }

    public function testInitialize()
    {
        
    }
}