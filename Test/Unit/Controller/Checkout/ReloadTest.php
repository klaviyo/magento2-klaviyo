<?php

namespace Klaviyo\Reclaim\Test\Unit\Controller\Checkout;

use Klaviyo\Reclaim\Controller\Checkout\Reload;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\TestCase;

class ReloadTest extends TestCase
{
    public function testExecuteReturnsSuccess()
    {
        $result = $this->createMock(Json::class);
        $result->expects($this->once())->method('setData')->with(['success' => 1])->willReturnSelf();
        $factory = $this->createMock(JsonFactory::class);
        $factory->method('create')->willReturn($result);
        $controller = $this->getMockBuilder(Reload::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        $property = new \ReflectionProperty(Reload::class, 'resultJsonFactory');
        $property->setValue($controller, $factory);
        $this->assertSame($result, $controller->execute());
    }
}
