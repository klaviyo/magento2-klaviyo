<?php

namespace \Klaviyo\Reclaim\Test\Unit\Block;

use \PHPUnit\Framewok\TestCase;
use \Klaviyo\Reclaim\Block\Initalize;

class InitializeTest extends TestCase
{
    /**
     * @var Initialize
     */
    private $object;

    protected function setUp()
    {
        $this->object = new Initalize();
    }

    public function testInitalizeInstance()
    {
        $this->assertInstanceOf(Initialize::class, $this->object);
    }

    public function testInitialize()
    {
        
    }
}