<?php

namespace Klaviyo\Reclaim\Test\Unit\Controller\Checkout;

use Klaviyo\Reclaim\Controller\Checkout\Cart;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use PHPUnit\Framework\TestCase;

class CartTest extends TestCase
{
    public function testMissingCustomerQuoteRedirectsWithRemainingParameters()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParams')->willReturn(['quote_id' => 'kx_identifier_' . base64_encode('7'), 'utm' => 'email']);
        $repository = $this->createMock(QuoteRepository::class);
        $repository->expects($this->once())->method('getActiveForCustomer')->with('7')->willThrowException(new NoSuchEntityException(new \Magento\Framework\Phrase('Quote not found')));
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects($this->once())->method('setPath')->with('checkout/cart', ['_query' => ['utm' => 'email']]);
        $factory = $this->createMock(RedirectFactory::class);
        $factory->method('create')->willReturn($redirect);
        $controller = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()->onlyMethods([])->getMock();
        foreach (['request' => $request, 'quoteRepository' => $repository, 'resultRedirectFactory' => $factory] as $key => $value) {
            $property = new \ReflectionProperty(Cart::class, $key);
            $property->setValue($controller, $value);
        }
        $this->assertSame($redirect, $controller->execute());
    }
}
