<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Observer\UserProfileNewsletterSubscribeObserver;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Klaviyo\Reclaim\Test\Fakes\SubscriberFake as Subscriber;
use Klaviyo\Reclaim\Test\Fakes\ObserverFake as Observer;

class UserProfileNewsletterSubscribeObserverTest extends TestCase
{
    /**
    * @var UserProfileNewsletterSubscribeObserver
    */
    protected $object;

    const IS_ENABLED = TRUE;
    const CUSTOMER_ID = 12345;
    const CUSTOMER_EMAIL = 'test@example.com';
    const CUSTOMER_FIRST_NAME = 'Joe';
    const CUSTOMER_LAST_NAME = 'Smith';

    public function setUp()
    {
        $dataMock = $this->createMock(Data::class);
        $dataMock->method('subscribeEmailToKlaviyoList')
            ->with(
                $this->equalTo(self::CUSTOMER_EMAIL),
                $this->equalTo(self::CUSTOMER_FIRST_NAME),
                $this->equalTo(self::CUSTOMER_LAST_NAME)
            )
            ->willReturn(TRUE);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('isEnabled')->willReturn(self::IS_ENABLED);

        $requestMock = $this->createMock(RequestInterface::class);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getEmail')->willReturn(self::CUSTOMER_EMAIL);
        $customerMock->method('getFirstname')->willReturn(self::CUSTOMER_FIRST_NAME);
        $customerMock->method('getLastname')->willReturn(self::CUSTOMER_LAST_NAME);
        $customerRepositoryInterfaceMock = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepositoryInterfaceMock->method('getById')
            ->with($this->equalTo(self::CUSTOMER_ID))
            ->willReturn($customerMock);

        $this->object = new UserProfileNewsletterSubscribeObserver(
            $dataMock,
            $scopeSettingMock,
            $requestMock,
            $customerRepositoryInterfaceMock
        );
    }
    public function testNewsletterSubscribeObserverInstance()
    {
        $this->assertInstanceOf(UserProfileNewsletterSubscribeObserver::class, $this->object);
    }

    public function testExecute()
    {
        $didNotFail = TRUE;

        $subscriberMock = $this->createMock(Subscriber::class);
        $subscriberMock->method('isStatusChanged')->willReturn(TRUE);
        $subscriberMock->method('getCustomerId')->willReturn(self::CUSTOMER_ID);
        $subscriberMock->method('isSubscribed')->willReturn(TRUE);
        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getDataObject')->willReturn($subscriberMock);

        try {
            $this->object->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = FALSE;
        }

        $this->assertTrue($didNotFail);
    }
}