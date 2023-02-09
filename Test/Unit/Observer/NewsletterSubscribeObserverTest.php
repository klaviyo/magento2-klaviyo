<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Klaviyo\Reclaim\Observer\NewsletterSubscribeObserver;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;

class NewsletterSubscribeObserverTest extends TestCase
{
    /**
     * @var NewsletterSubscribeObserver
     */
    protected $object;

    protected function setUp(): void
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('subscribeEmailToKlaviyoList')
            ->with($this->equalTo(SampleCustomer::CUSTOMER_EMAIL))
            ->willReturn(true);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('isEnabled')->willReturn(SampleExtension::IS_ENABLED);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')
            ->with($this->equalTo('email'))
            ->willReturn(SampleCustomer::CUSTOMER_EMAIL);

        $this->object = new NewsletterSubscribeObserver(
            $dataMock,
            $scopeSettingMock,
            $requestMock
        );
    }
    public function testNewsletterSubscribeObserverInstance()
    {
        $this->assertInstanceOf(NewsletterSubscribeObserver::class, $this->object);
    }

    public function testExecute()
    {
        $didNotFail = true;
        $observerMock = $this->createMock(Observer::class);

        try {
            $this->object->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = false;
        }

        $this->assertTrue($didNotFail);
    }
}
