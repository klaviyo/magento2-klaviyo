<?php

namespace Klaviyo\Reclaim\Test\Unit\Plugin\Customer\Model;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleCustomer;
use Klaviyo\Reclaim\Plugin\Customer\Model\CustomerData;
use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Api\Data\CustomerInterface;

class CustomerDataTest extends TestCase
{
    /**
     * @var Customer
     */
    protected $customerData;

    const LASTNAME = 'lastname';
    const EMAIL = 'email';

    protected function setUp(): void
    {
        $customerInterfaceMock = $this->createMock(CustomerInterface::class);
        $customerInterfaceMock->method('getLastname')->willReturn(SampleCustomer::CUSTOMER_LAST_NAME);
        $customerInterfaceMock->method('getEmail')->willReturn(SampleCustomer::CUSTOMER_EMAIL);

        $currentCustomerMock = $this->createMock(CurrentCustomer::class);
        $currentCustomerMock->method('getCustomerId')->willReturn(SampleCustomer::CUSTOMER_ID);
        $currentCustomerMock->method('getCustomer')->willReturn($customerInterfaceMock);

        $this->customerData = new CustomerData(
            $currentCustomerMock
        );
    }

    public function testCustomerDataInstance()
    {
        $this->assertInstanceOf(CustomerData::class, $this->customerData);
    }

    public function testAfterGetSectionData()
    {
        $result = [];
        $expectedResult = [
            self::LASTNAME => SampleCustomer::CUSTOMER_LAST_NAME,
            self::EMAIL => SampleCustomer::CUSTOMER_EMAIL
        ];
        $customerMock = $this->createMock(Customer::class);
        $actualResult = $this->customerData->afterGetSectionData($customerMock, $result);
        $this->assertSame($expectedResult[self::LASTNAME], $actualResult[self::LASTNAME]);
        $this->assertSame($expectedResult[self::EMAIL], $actualResult[self::EMAIL]);
    }
}
