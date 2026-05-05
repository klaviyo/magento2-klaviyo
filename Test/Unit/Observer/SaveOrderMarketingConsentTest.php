<?php

/**
 * Stub Magento\Framework\Event\Observer and related classes so the observer can load
 * without the full Magento framework installed.
 */

namespace Magento\Framework\Event {
    if (!class_exists(\Magento\Framework\Event\Observer::class, false)) {
        class Observer
        {
            public function getEvent()
            {
                return new Event();
            }
        }
    }
    if (!class_exists(\Magento\Framework\Event\Event::class, false)) {
        class Event
        {
            public function getOrder()
            {
                return null;
            }
            public function getQuote()
            {
                return null;
            }
        }
    }
    if (!interface_exists(\Magento\Framework\Event\ObserverInterface::class, false)) {
        interface ObserverInterface
        {
            public function execute(Observer $observer);
        }
    }
}

/**
 * Stub Magento\Framework\App\Helper\AbstractHelper so ScopeSetting can load.
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

namespace Klaviyo\Reclaim\Test\Unit\Observer {

    use PHPUnit\Framework\TestCase;
    use Klaviyo\Reclaim\Helper\Logger;
    use Klaviyo\Reclaim\Helper\ScopeSetting;
    use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
    use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoApiException;
    use Klaviyo\Reclaim\Observer\SaveOrderMarketingConsent;
    use Magento\Framework\Event\Observer;

    /**
     * Testable subclass that replaces buildKlaviyoV3Api() with an injected mock.
     */
    class TestableSaveOrderMarketingConsent extends SaveOrderMarketingConsent
    {
        private $apiMock;

        public function __construct(Logger $logger, ScopeSetting $scopeSetting, KlaviyoV3Api $apiMock)
        {
            parent::__construct($logger, $scopeSetting);
            $this->apiMock = $apiMock;
        }

        protected function buildKlaviyoV3Api($storeId = null): KlaviyoV3Api
        {
            return $this->apiMock;
        }
    }

    /**
     * Stub order that captures setData calls.
     */
    class StubOrder
    {
        public $data = [];

        public function setData($key, $value)
        {
            $this->data[$key] = $value;
        }
    }

    /**
     * Stub shipping address.
     */
    class StubAddress
    {
        private $phone;

        public function __construct($phone)
        {
            $this->phone = $phone;
        }

        public function getTelephone()
        {
            return $this->phone;
        }
    }

    /**
     * Stub quote.
     */
    class StubQuote
    {
        private $mobileConsent;
        private $emailConsent;
        private $storeId;
        private $customerEmail;
        private $address;

        public function __construct($mobileConsent, $emailConsent, $storeId, $customerEmail, $address)
        {
            $this->mobileConsent = $mobileConsent;
            $this->emailConsent = $emailConsent;
            $this->storeId = $storeId;
            $this->customerEmail = $customerEmail;
            $this->address = $address;
        }

        public function getKlMobileConsent()
        {
            return $this->mobileConsent;
        }

        public function getKlEmailConsent()
        {
            return $this->emailConsent;
        }

        public function getStoreId()
        {
            return $this->storeId;
        }

        public function getCustomerEmail()
        {
            return $this->customerEmail;
        }

        public function getShippingAddress()
        {
            return $this->address;
        }
    }

    /**
     * Stub event that holds order and quote.
     */
    class StubEvent extends \Magento\Framework\Event\Event
    {
        private $order;
        private $quote;

        public function __construct($order, $quote)
        {
            $this->order = $order;
            $this->quote = $quote;
        }

        public function getOrder()
        {
            return $this->order;
        }

        public function getQuote()
        {
            return $this->quote;
        }
    }

    /**
     * Stub observer that holds a stub event.
     */
    class StubObserver extends Observer
    {
        private $event;

        public function __construct($event)
        {
            $this->event = $event;
        }

        public function getEvent()
        {
            return $this->event;
        }
    }

    class SaveOrderMarketingConsentTest extends TestCase
    {
        private function makeMagentoObserver($mobileConsent, $emailConsent, $storeId = 1)
        {
            $order = new StubOrder();
            $address = new StubAddress('555-0100');
            $quote = new StubQuote($mobileConsent, $emailConsent, $storeId, 'buyer@example.com', $address);
            $event = new StubEvent($order, $quote);
            return new StubObserver($event);
        }

        private function makeScopeMock($mobileActive, $emailActive, array $channels)
        {
            $mock = $this->getMockBuilder(ScopeSetting::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->method('getMobileConsentIsActive')->willReturn($mobileActive ? '1' : null);
            $mock->method('getConsentAtCheckoutEmailIsActive')->willReturn($emailActive ? '1' : null);
            $mock->method('getMobileConsentListId')->willReturn('mobile-list-id');
            $mock->method('getConsentAtCheckoutEmailListId')->willReturn('email-list-id');
            $mock->method('getMobileConsentChannels')->willReturn($channels);
            $channelsCopy = $channels;
            $mock->method('isMobileChannelEnabled')->willReturnCallback(
                function ($storeId, $channel) use ($channelsCopy) {
                    return in_array($channel, $channelsCopy, true);
                }
            );
            $mock->method('getPublicApiKey')->willReturn('pk');
            $mock->method('getPrivateApiKey')->willReturn('sk');
            return $mock;
        }

        private function makeLoggerMock()
        {
            return $this->getMockBuilder(Logger::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        private function makeApiMock()
        {
            return $this->getMockBuilder(KlaviyoV3Api::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        public function test_sms_only_enabled_mobile_consent_true_subscribe_has_sms_key_not_whatsapp()
        {
            $scope = $this->makeScopeMock(true, false, ['sms']);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver('1', null);

            $capturedProfiles = null;
            $api->expects($this->once())
                ->method('subscribeMembersToList')
                ->with(
                    'mobile-list-id',
                    $this->callback(function ($profiles) use (&$capturedProfiles) {
                        $capturedProfiles = $profiles;
                        return true;
                    })
                );

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $observer->execute($magentoObserver);

            $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('sms', $subscriptions);
            $this->assertArrayNotHasKey('whatsapp', $subscriptions);
        }

        public function test_whatsapp_only_enabled_mobile_consent_true_subscribe_has_whatsapp_key_not_sms()
        {
            $scope = $this->makeScopeMock(true, false, ['whatsapp']);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver('1', null);

            $capturedProfiles = null;
            $api->expects($this->once())
                ->method('subscribeMembersToList')
                ->with(
                    'mobile-list-id',
                    $this->callback(function ($profiles) use (&$capturedProfiles) {
                        $capturedProfiles = $profiles;
                        return true;
                    })
                );

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $observer->execute($magentoObserver);

            $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('whatsapp', $subscriptions);
            $this->assertArrayNotHasKey('sms', $subscriptions);
        }

        public function test_both_channels_enabled_mobile_consent_true_subscribe_has_sms_and_whatsapp_keys()
        {
            $scope = $this->makeScopeMock(true, false, ['sms', 'whatsapp']);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver('1', null);

            $capturedProfiles = null;
            $api->expects($this->once())
                ->method('subscribeMembersToList')
                ->with(
                    'mobile-list-id',
                    $this->callback(function ($profiles) use (&$capturedProfiles) {
                        $capturedProfiles = $profiles;
                        return true;
                    })
                );

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $observer->execute($magentoObserver);

            $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('sms', $subscriptions);
            $this->assertArrayHasKey('whatsapp', $subscriptions);
            $this->assertSame('SUBSCRIBED', $subscriptions['sms']['marketing']['consent']);
            $this->assertSame('SUBSCRIBED', $subscriptions['whatsapp']['marketing']['consent']);
        }

        public function test_email_only_mobile_consent_false_subscribe_called_once_with_email_key_no_mobile()
        {
            $scope = $this->makeScopeMock(false, true, []);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver(null, '1');

            $capturedListId = null;
            $capturedProfiles = null;
            $api->expects($this->once())
                ->method('subscribeMembersToList')
                ->with(
                    $this->callback(function ($listId) use (&$capturedListId) {
                        $capturedListId = $listId;
                        return true;
                    }),
                    $this->callback(function ($profiles) use (&$capturedProfiles) {
                        $capturedProfiles = $profiles;
                        return true;
                    })
                );

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $observer->execute($magentoObserver);

            $this->assertSame('email-list-id', $capturedListId);
            $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('email', $subscriptions);
            $this->assertArrayNotHasKey('sms', $subscriptions);
            $this->assertArrayNotHasKey('whatsapp', $subscriptions);
        }

        public function test_email_and_mobile_both_consents_true_two_subscribe_calls_with_correct_keys()
        {
            $scope = $this->makeScopeMock(true, true, ['sms', 'whatsapp']);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver('1', '1');

            $calls = [];
            $api->expects($this->exactly(2))
                ->method('subscribeMembersToList')
                ->willReturnCallback(function ($listId, $profiles) use (&$calls) {
                    $calls[] = ['listId' => $listId, 'profiles' => $profiles];
                });

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $observer->execute($magentoObserver);

            $this->assertCount(2, $calls);

            $emailCall = null;
            $mobileCall = null;
            foreach ($calls as $call) {
                if ($call['listId'] === 'email-list-id') {
                    $emailCall = $call;
                } elseif ($call['listId'] === 'mobile-list-id') {
                    $mobileCall = $call;
                }
            }

            $this->assertNotNull($emailCall, 'Expected an email subscribe call');
            $this->assertNotNull($mobileCall, 'Expected a mobile subscribe call');

            $emailSubs = $emailCall['profiles'][0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('email', $emailSubs);

            $mobileSubs = $mobileCall['profiles'][0]['attributes']['subscriptions'];
            $this->assertArrayHasKey('sms', $mobileSubs);
            $this->assertArrayHasKey('whatsapp', $mobileSubs);
        }

        public function test_no_webhook_call_constructor_does_not_accept_webhook_parameter()
        {
            $reflection = new \ReflectionClass(SaveOrderMarketingConsent::class);
            $constructor = $reflection->getConstructor();
            $paramTypes = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                $paramTypes[] = $type ? $type->getName() : '';
            }
            $this->assertNotContains('Klaviyo\Reclaim\Helper\Webhook', $paramTypes);
            $this->assertCount(2, $constructor->getParameters());
        }

        public function test_v3_error_does_not_fail_order_execute_returns_observer()
        {
            $scope = $this->makeScopeMock(true, false, ['sms']);
            $api = $this->makeApiMock();
            $magentoObserver = $this->makeMagentoObserver('1', null);

            $api->method('subscribeMembersToList')
                ->willThrowException(new KlaviyoApiException('V3 error'));

            $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, $api);
            $result = $observer->execute($magentoObserver);
            $this->assertSame($observer, $result);
        }
    }
}
