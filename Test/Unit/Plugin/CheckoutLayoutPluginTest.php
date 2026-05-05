<?php

/**
 * Stub Magento\Framework\App\Helper\AbstractHelper so ScopeSetting can be loaded/mocked
 * without the full Magento framework installed in the test environment.
 */

namespace Magento\Framework\App\Helper {
    if (!class_exists(\Magento\Framework\App\Helper\AbstractHelper::class, false)) {
        abstract class AbstractHelper
        {
            public function __construct($context = null)
            {
            }
        }
    }
}

/**
 * Stub Magento Customer classes used by CheckoutLayoutPlugin.
 */
namespace Magento\Customer\Model {
    if (!class_exists(\Magento\Customer\Model\Session::class, false)) {
        class Session
        {
            public function isLoggedIn(): bool
            {
                return false;
            }
            public function getCustomer()
            {
                return null;
            }
        }
    }
    if (!class_exists(\Magento\Customer\Model\Customer::class, false)) {
        class Customer
        {
            public function getData(): array
            {
                return [];
            }
            public function load($id): self
            {
                return $this;
            }
            public function getDefaultShippingAddress()
            {
                return false;
            }
        }
    }
    if (!class_exists(\Magento\Customer\Model\CustomerFactory::class, false)) {
        class CustomerFactory
        {
            public function create(): Customer
            {
                return new Customer();
            }
        }
    }
}

/**
 * Stub the LayoutProcessor subject class.
 */
namespace Magento\Checkout\Block\Checkout {
    if (!class_exists(\Magento\Checkout\Block\Checkout\LayoutProcessor::class, false)) {
        class LayoutProcessor
        {
        }
    }
}

namespace Klaviyo\Reclaim\Test\Unit\Plugin {

    use PHPUnit\Framework\TestCase;
    use Klaviyo\Reclaim\Plugin\CheckoutLayoutPlugin;
    use Klaviyo\Reclaim\Helper\ScopeSetting;
    use Magento\Customer\Model\Customer;
    use Magento\Customer\Model\CustomerFactory;
    use Magento\Customer\Model\Session;
    use Magento\Checkout\Block\Checkout\LayoutProcessor;

    class CheckoutLayoutPluginTest extends TestCase
    {
        private function buildBaseJsLayout(): array
        {
            return [
                'components' => [
                    'checkout' => [
                        'children' => [
                            'steps' => [
                                'children' => [
                                    'shipping-step' => [
                                        'children' => [
                                            'shippingAddress' => [
                                                'children' => [
                                                    'shipping-address-fieldset' => ['children' => []],
                                                    'before-form' => ['children' => []],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        private function makeScopeMock(bool $mobileActive): ScopeSetting
        {
            $mock = $this->getMockBuilder(ScopeSetting::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getMobileConsentIsActive')->willReturn($mobileActive ? '1' : null);
            $mock->method('getMobileConsentLabelText')->willReturn('Mobile Label');
            $mock->method('getMobileConsentText')->willReturn('Mobile Description');
            $mock->method('getMobileConsentSortOrder')->willReturn('100');
            $mock->method('getConsentAtCheckoutEmailIsActive')->willReturn(null);
            return $mock;
        }

        public function test_afterProcess_mobile_inactive_no_consent_key_in_fieldset()
        {
            $scope = $this->makeScopeMock(false);
            $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
            $session->method('isLoggedIn')->willReturn(false);
            $factory = $this->getMockBuilder(CustomerFactory::class)->disableOriginalConstructor()->getMock();

            $plugin = new CheckoutLayoutPlugin($scope, $session, $factory);
            $result = $plugin->afterProcess($this->createMock(LayoutProcessor::class), $this->buildBaseJsLayout());

            $fieldset = $result['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['shipping-address-fieldset']['children'];
            $this->assertArrayNotHasKey('kl_mobile_consent', $fieldset);
            $this->assertArrayNotHasKey('kl_sms_consent', $fieldset);
        }

        public function test_afterProcess_mobile_active_no_default_address_adds_kl_mobile_consent_to_fieldset()
        {
            $scope = $this->makeScopeMock(true);
            $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
            $session->method('isLoggedIn')->willReturn(false);
            $factory = $this->getMockBuilder(CustomerFactory::class)->disableOriginalConstructor()->getMock();

            $plugin = new CheckoutLayoutPlugin($scope, $session, $factory);
            $result = $plugin->afterProcess($this->createMock(LayoutProcessor::class), $this->buildBaseJsLayout());

            $fieldset = $result['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['shipping-address-fieldset']['children'];
            $this->assertArrayHasKey('kl_mobile_consent', $fieldset);
            $this->assertArrayNotHasKey('kl_sms_consent', $fieldset);
            $this->assertSame('kl_mobile_consent', $fieldset['kl_mobile_consent']['config']['id']);
            $this->assertStringContainsString('kl_mobile_consent', $fieldset['kl_mobile_consent']['dataScope']);
        }

        public function test_afterProcess_mobile_active_with_default_address_adds_mobile_consent_and_phone_to_before_form()
        {
            $scope = $this->makeScopeMock(true);

            $addressStub = new class {
                public function getTelephone(): string
                {
                    return '555-1234';
                }
            };

            $customerMock = $this->getMockBuilder(Customer::class)->disableOriginalConstructor()->getMock();
            $customerMock->method('getData')->willReturn(['entity_id' => 42]);
            $customerMock->method('load')->willReturnSelf();
            $customerMock->method('getDefaultShippingAddress')->willReturn($addressStub);

            $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
            $session->method('isLoggedIn')->willReturn(true);
            $session->method('getCustomer')->willReturn($customerMock);

            $factory = $this->getMockBuilder(CustomerFactory::class)->disableOriginalConstructor()->getMock();
            $factory->method('create')->willReturn($customerMock);

            $plugin = new CheckoutLayoutPlugin($scope, $session, $factory);
            $result = $plugin->afterProcess($this->createMock(LayoutProcessor::class), $this->buildBaseJsLayout());

            $beforeForm = $result['components']['checkout']['children']['steps']['children']
                ['shipping-step']['children']['shippingAddress']['children']
                ['before-form']['children'];
            $this->assertArrayHasKey('kl_mobile_consent', $beforeForm);
            $this->assertArrayHasKey('kl_mobile_phone_number', $beforeForm);
            $this->assertArrayNotHasKey('kl_sms_consent', $beforeForm);
            $this->assertArrayNotHasKey('kl_sms_phone_number', $beforeForm);
        }
    }
}
