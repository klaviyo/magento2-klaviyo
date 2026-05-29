<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Helper\Logger;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Klaviyo\Reclaim\KlaviyoV3Sdk\Exception\KlaviyoApiException;
use Klaviyo\Reclaim\Observer\SaveOrderMarketingConsent;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubAddress;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubEvent;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubObserver;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubOrder;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\StubQuote;
use Klaviyo\Reclaim\Test\Unit\Observer\Stubs\TestableSaveOrderMarketingConsent;
use Klaviyo\Reclaim\Util\PhoneFormatter;

class SaveOrderMarketingConsentTest extends TestCase
{
    private function makeMagentoObserver($mobileConsent, $emailConsent, $storeId = 1, $phone = '(202) 555-0100', $country = 'US')
    {
        $order = new StubOrder();
        $address = new StubAddress($phone, $country);
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

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
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

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
        $this->assertArrayHasKey('whatsapp', $subscriptions);
        $this->assertArrayNotHasKey('sms', $subscriptions);
    }

    public function test_both_channels_enabled_mobile_consent_true_two_separate_subscribe_calls()
    {
        $scope = $this->makeScopeMock(true, false, ['sms', 'whatsapp']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', null);

        $calls = [];
        $api->expects($this->exactly(2))
            ->method('subscribeMembersToList')
            ->willReturnCallback(function ($listId, $profiles) use (&$calls) {
                $calls[] = ['listId' => $listId, 'profiles' => $profiles];
            });

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $this->assertCount(2, $calls);
        // Both calls go to the same mobile list.
        $this->assertSame('mobile-list-id', $calls[0]['listId']);
        $this->assertSame('mobile-list-id', $calls[1]['listId']);

        // Each call carries exactly one channel in its subscriptions payload.
        $sub0 = $calls[0]['profiles'][0]['attributes']['subscriptions'];
        $sub1 = $calls[1]['profiles'][0]['attributes']['subscriptions'];
        $this->assertArrayHasKey('sms', $sub0);
        $this->assertArrayNotHasKey('whatsapp', $sub0);
        $this->assertSame('SUBSCRIBED', $sub0['sms']['marketing']['consent']);
        $this->assertArrayHasKey('whatsapp', $sub1);
        $this->assertArrayNotHasKey('sms', $sub1);
        $this->assertSame('SUBSCRIBED', $sub1['whatsapp']['marketing']['consent']);
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

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $this->assertSame('email-list-id', $capturedListId);
        $subscriptions = $capturedProfiles[0]['attributes']['subscriptions'];
        $this->assertArrayHasKey('email', $subscriptions);
        $this->assertArrayNotHasKey('sms', $subscriptions);
        $this->assertArrayNotHasKey('whatsapp', $subscriptions);
    }

    public function test_email_and_mobile_both_consents_true_three_subscribe_calls_one_per_channel()
    {
        $scope = $this->makeScopeMock(true, true, ['sms', 'whatsapp']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', '1');

        $calls = [];
        $api->expects($this->exactly(3))
            ->method('subscribeMembersToList')
            ->willReturnCallback(function ($listId, $profiles) use (&$calls) {
                $calls[] = ['listId' => $listId, 'profiles' => $profiles];
            });

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $this->assertCount(3, $calls);

        // Partition calls by what they target.
        $emailCalls = [];
        $smsCalls = [];
        $whatsappCalls = [];
        foreach ($calls as $call) {
            $subs = $call['profiles'][0]['attributes']['subscriptions'];
            if (array_key_exists('email', $subs)) {
                $emailCalls[] = $call;
            } elseif (array_key_exists('sms', $subs)) {
                $smsCalls[] = $call;
            } elseif (array_key_exists('whatsapp', $subs)) {
                $whatsappCalls[] = $call;
            }
        }

        $this->assertCount(1, $emailCalls, 'Expected exactly one email subscribe call');
        $this->assertCount(1, $smsCalls, 'Expected exactly one sms subscribe call');
        $this->assertCount(1, $whatsappCalls, 'Expected exactly one whatsapp subscribe call');

        $this->assertSame('email-list-id', $emailCalls[0]['listId']);
        $this->assertSame('mobile-list-id', $smsCalls[0]['listId']);
        $this->assertSame('mobile-list-id', $whatsappCalls[0]['listId']);
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
        $this->assertCount(3, $constructor->getParameters());
    }

    public function test_v3_error_does_not_fail_order_execute_returns_observer()
    {
        $scope = $this->makeScopeMock(true, false, ['sms']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', null);

        $api->method('subscribeMembersToList')
            ->willThrowException(new KlaviyoApiException('V3 error'));

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $result = $observer->execute($magentoObserver);
        $this->assertSame($observer, $result);
    }

    public function test_mobile_consent_phone_unparseable_skips_mobile_subscribe_call()
    {
        $scope = $this->makeScopeMock(true, false, ['sms']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', null, 1, 'not-a-number', 'US');

        $api->expects($this->never())->method('subscribeMembersToList');

        $logger = $this->makeLoggerMock();
        $logger->expects($this->atLeastOnce())
            ->method('log')
            ->with($this->stringContains('Mobile subscribe skipped'));

        $observer = new TestableSaveOrderMarketingConsent($logger, $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);
    }

    public function test_email_consent_unaffected_by_mobile_phone_failure()
    {
        $scope = $this->makeScopeMock(true, true, ['sms']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', '1', 1, 'not-a-number', 'US');

        $calls = [];
        $api->method('subscribeMembersToList')
            ->willReturnCallback(function ($listId, $profiles) use (&$calls) {
                $calls[] = ['listId' => $listId, 'profiles' => $profiles];
            });

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $this->assertCount(1, $calls, 'Only the email subscribe should fire when mobile phone is unparseable');
        $this->assertSame('email-list-id', $calls[0]['listId']);
    }

    public function test_both_channels_enabled_one_channel_failure_does_not_block_other_channel()
    {
        // Per-channel subscribe semantics: if the first call (sms) throws,
        // the second call (whatsapp) must still fire.
        $scope = $this->makeScopeMock(true, false, ['sms', 'whatsapp']);
        $api = $this->makeApiMock();
        $magentoObserver = $this->makeMagentoObserver('1', null);

        $calls = [];
        $callCount = 0;
        $api->method('subscribeMembersToList')
            ->willReturnCallback(function ($listId, $profiles) use (&$calls, &$callCount) {
                $callCount++;
                $calls[] = ['listId' => $listId, 'profiles' => $profiles];
                if ($callCount === 1) {
                    throw new KlaviyoApiException('Simulated SMS subscribe failure');
                }
            });

        $observer = new TestableSaveOrderMarketingConsent($this->makeLoggerMock(), $scope, new PhoneFormatter(), $api);
        $observer->execute($magentoObserver);

        $this->assertCount(2, $calls, 'Second channel subscribe must still fire after first throws');
        $subs1 = $calls[0]['profiles'][0]['attributes']['subscriptions'];
        $subs2 = $calls[1]['profiles'][0]['attributes']['subscriptions'];
        $this->assertArrayHasKey('sms', $subs1, 'First call should target sms (the one that throws)');
        $this->assertArrayHasKey('whatsapp', $subs2, 'Second call should still target whatsapp despite first failure');
    }
}
