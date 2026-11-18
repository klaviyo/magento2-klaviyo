<?php

namespace Klaviyo\Reclaim\Test\Unit\Controller\Checkout;

use Klaviyo\Reclaim\Controller\Checkout\Email;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testInvalidEmailDoesNotSaveQuote()
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->never())->method('save');
        $cart = $this->createMock(Cart::class);
        $cart->method('getQuote')->willReturn($quote);
        $manager = $this->createMock(ObjectManagerInterface::class);
        $manager->method('create')->with(Cart::class)->willReturn($cart);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->with('email')->willReturn('invalid');
        $controller = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()->onlyMethods(['getRequest'])->getMock();
        $controller->method('getRequest')->willReturn($request);
        foreach (['_objectManager' => $manager, 'resultJsonFactory' => $this->createMock(JsonFactory::class)] as $key => $value) {
            $property = new \ReflectionProperty(Email::class, $key);
            $property->setValue($controller, $value);
        }
        $this->assertNull($controller->execute());
    }
}
