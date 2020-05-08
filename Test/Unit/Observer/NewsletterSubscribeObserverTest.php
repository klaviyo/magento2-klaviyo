<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
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

    const IS_ENABLED = TRUE;
    const SUBSCRIBER_EMAIL = 'test@example.com';

    protected function setUp()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('subscribeEmailToKlaviyoList')
            ->with($this->equalTo(self::SUBSCRIBER_EMAIL))
            ->willReturn(TRUE);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('isEnabled')->willReturn(self::IS_ENABLED);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')
            ->with($this->equalTo('email'))
            ->willReturn(self::SUBSCRIBER_EMAIL);

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
        $didNotFail = TRUE;
        $observerMock = $this->createMock(Observer::class);

        try {
            $this->object->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = FALSE;
        }

        $this->assertTrue($didNotFail);
    }
}