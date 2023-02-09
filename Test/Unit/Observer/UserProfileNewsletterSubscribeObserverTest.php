<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Klaviyo\Reclaim\Test\Fakes\SubscriberFake as Subscriber;
use Klaviyo\Reclaim\Test\Fakes\ObserverFake as Observer;
use Klaviyo\Reclaim\Observer\UserProfileNewsletterSubscribeObserver;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class UserProfileNewsletterSubscribeObserverTest extends TestCase
{
    /**
    * @var UserProfileNewsletterSubscribeObserver
    */
    protected $userProfileNewsletterSubscribeObserver;

    public function setUp(): void
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('subscribeEmailToKlaviyoList')
            ->with(
                $this->equalTo(SampleCustomer::CUSTOMER_EMAIL),
                $this->equalTo(SampleCustomer::CUSTOMER_FIRST_NAME),
                $this->equalTo(SampleCustomer::CUSTOMER_LAST_NAME)
            )
            ->willReturn(true);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('isEnabled')->willReturn(SampleExtension::IS_ENABLED);

        $requestMock = $this->createMock(RequestInterface::class);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getEmail')->willReturn(SampleCustomer::CUSTOMER_EMAIL);
        $customerMock->method('getFirstname')->willReturn(SampleCustomer::CUSTOMER_FIRST_NAME);
        $customerMock->method('getLastname')->willReturn(SampleCustomer::CUSTOMER_LAST_NAME);
        $customerRepositoryInterfaceMock = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepositoryInterfaceMock->method('getById')
            ->with($this->equalTo(SampleCustomer::CUSTOMER_ID))
            ->willReturn($customerMock);

        $this->userProfileNewsletterSubscribeObserver = new UserProfileNewsletterSubscribeObserver(
            $dataMock,
            $scopeSettingMock,
            $requestMock,
            $customerRepositoryInterfaceMock
        );
    }
    public function testNewsletterSubscribeObserverInstance()
    {
        $this->assertInstanceOf(UserProfileNewsletterSubscribeObserver::class, $this->userProfileNewsletterSubscribeObserver);
    }

    public function testExecute()
    {
        $didNotFail = true;

        $subscriberMock = $this->createMock(Subscriber::class);
        $subscriberMock->method('isStatusChanged')->willReturn(true);
        $subscriberMock->method('getCustomerId')->willReturn(SampleCustomer::CUSTOMER_ID);
        $subscriberMock->method('isSubscribed')->willReturn(true);
        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getDataObject')->willReturn($subscriberMock);

        try {
            $this->userProfileNewsletterSubscribeObserver->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = false;
        }

        $this->assertTrue($didNotFail);
    }
}
