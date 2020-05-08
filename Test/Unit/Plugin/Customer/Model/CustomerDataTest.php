<?php

namespace Klaviyo\Reclaim\Test\Unit\Plugin\Customer\Model;

use PHPUnit\Framework\TestCase;
use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Api\Data\CustomerInterface;
use Klaviyo\Reclaim\Plugin\Customer\Model\CustomerData;

class CustomerDataTest extends TestCase
{
    /**
     * @var Customer
     */
    protected $object;

    protected function setUp()
    {
        $customerInterfaceMock = $this->createMock(CustomerInterface::class);
        $customerInterfaceMock->method('getLastname')->willReturn('Smith');
        $customerInterfaceMock->method('getEmail')->willReturn('test@example.com');

        $currentCustomerMock = $this->createMock(CurrentCustomer::class);
        $currentCustomerMock->method('getCustomerId')->willReturn(12345);
        $currentCustomerMock->method('getCustomer')->willReturn($customerInterfaceMock);

        $this->object = new CustomerData(
            $currentCustomerMock
        );
    }

    public function testCustomerDataInstance()
    {
        $this->assertInstanceOf(CustomerData::class, $this->object);
    }

    public function testAfterGetSectionData()
    {
        $result = array();
        $expectedResult = array(
            'lastname' => 'Smith',
            'email' => 'test@example.com'
        );
        $customerMock = $this->createMock(Customer::class);
        $actualResult = $this->object->afterGetSectionData($customerMock, $result);
        $this->assertSame($expectedResult['lastname'], $actualResult['lastname']);
        $this->assertSame($expectedResult['email'], $actualResult['email']);
    }
}