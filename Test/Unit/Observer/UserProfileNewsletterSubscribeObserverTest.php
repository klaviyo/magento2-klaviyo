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

    public function setUp()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('subscribeEmailToKlaviyoList')
            ->with(
                $this->equalTo(SampleCustomer::CUSTOMER_EMAIL),
                $this->equalTo(SampleCustomer::CUSTOMER_FIRST_NAME),
                $this->equalTo(SampleCustomer::CUSTOMER_LAST_NAME)
            )
            ->willReturn(TRUE);

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
        $didNotFail = TRUE;

        $subscriberMock = $this->createMock(Subscriber::class);
        $subscriberMock->method('isStatusChanged')->willReturn(TRUE);
        $subscriberMock->method('getCustomerId')->willReturn(SampleCustomer::CUSTOMER_ID);
        $subscriberMock->method('isSubscribed')->willReturn(TRUE);
        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getDataObject')->willReturn($subscriberMock);

        try {
            $this->userProfileNewsletterSubscribeObserver->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = FALSE;
        }

        $this->assertTrue($didNotFail);
    }
}
