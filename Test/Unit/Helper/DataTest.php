<?php

namespace Klaviyo\Reclaim\Test\Unit\Helper;

use Klaviyo\Reclaim\Helper\Data;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    public function testCartPayloadCanBeStoredAndCleared()
    {
        $helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $payload = ['AddedItemQuantity' => 2];
        $helper->setObserverAtcPayload($payload);
        $this->assertSame($payload, $helper->getObserverAtcPayload());
        $helper->unsetObserverAtcPayload();
        $this->assertNull($helper->getObserverAtcPayload());
    }
}
