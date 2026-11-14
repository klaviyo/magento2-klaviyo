<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Klaviyo\Reclaim\Observer\NewsletterSubscribeObserver;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Newsletter\Model\Subscriber;

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

        $customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);

        $this->object = new NewsletterSubscribeObserver(
            $dataMock,
            $scopeSettingMock,
            $customerRepositoryMock
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

    public function testUnsubscribePassesSubscriberStoreId()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->expects($this->once())
            ->method('unsubscribeEmailFromKlaviyoList')
            ->with('guest@example.com', 7);

        $this->buildObserver($dataMock)->execute(
            $this->buildEvent(Subscriber::STATUS_UNSUBSCRIBED, 7)
        );
    }

    public function testSubscribePassesSubscriberStoreId()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->expects($this->once())
            ->method('subscribeEmailToKlaviyoList')
            ->with('guest@example.com', 'Jane', 'Doe', 7);

        $this->buildObserver($dataMock)->execute(
            $this->buildEvent(Subscriber::STATUS_SUBSCRIBED, 7)
        );
    }

    public function testEnabledFlagIsResolvedForSubscriberStore()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->expects($this->once())
            ->method('unsubscribeEmailFromKlaviyoList')
            ->with('guest@example.com', 7);

        $this->buildObserver($dataMock, 7)->execute(
            $this->buildEvent(Subscriber::STATUS_UNSUBSCRIBED, 7)
        );
    }

    public function testDisabledForSubscriberStoreSkipsKlaviyo()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->expects($this->never())->method('unsubscribeEmailFromKlaviyoList');

        $this->buildObserver($dataMock, 7)->execute(
            $this->buildEvent(Subscriber::STATUS_UNSUBSCRIBED, 8)
        );
    }

    private function buildObserver(Data $dataMock, ?int $enabledStoreId = null): NewsletterSubscribeObserver
    {
        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        if ($enabledStoreId === null) {
            $scopeSettingMock->method('isEnabled')->willReturn(true);
        } else {
            $scopeSettingMock->method('isEnabled')->willReturnCallback(
                function ($storeId = null) use ($enabledStoreId) {
                    return $storeId === $enabledStoreId;
                }
            );
        }

        return new NewsletterSubscribeObserver(
            $dataMock,
            $scopeSettingMock,
            $this->createMock(CustomerRepositoryInterface::class)
        );
    }

    private function buildEvent(int $status, int $storeId): Observer
    {
        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->method('getId')->willReturn(11);
        $subscriber->method('getStatus')->willReturn($status);
        $subscriber->method('isStatusChanged')->willReturn(true);
        $subscriber->method('getStoreId')->willReturn($storeId);
        $subscriber->method('getEmail')->willReturn('guest@example.com');
        $subscriber->method('getFirstname')->willReturn('Jane');
        $subscriber->method('getLastname')->willReturn('Doe');

        $observer = $this->createMock(Observer::class);
        $observer->method('getDataObject')->willReturn($subscriber);

        return $observer;
    }
}
