<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\Model\Checkout;

use Klaviyo\Reclaim\Model\Checkout\ShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement as Subject;
use Magento\Quote\Model\QuoteRepository;
use PHPUnit\Framework\TestCase;

class ShippingInformationManagementTest extends TestCase
{
    private function buildPlugin($quote): ShippingInformationManagement
    {
        $repo = $this->getMockBuilder(QuoteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->method('getActive')->willReturn($quote);
        return new ShippingInformationManagement($repo);
    }

    private function buildAddressInfo($extAttributes): ShippingInformationInterface
    {
        $info = $this->getMockBuilder(ShippingInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $info->method('getExtensionAttributes')->willReturn($extAttributes);
        return $info;
    }

    public function test_beforeSaveAddressInformation_mobile_consent_sets_kl_sms_consent_on_quote()
    {
        $extAttributes = new class {
            public function getKlSmsConsent(): string
            {
                return '1';
            }
            public function getKlEmailConsent(): ?string
            {
                return null;
            }
        };

        $quote = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['setKlSmsConsent', 'setKlEmailConsent'])
            ->getMock();
        $quote->expects($this->once())
            ->method('setKlSmsConsent')
            ->with('1');
        $quote->expects($this->once())
            ->method('setKlEmailConsent')
            ->with(null);

        $plugin = $this->buildPlugin($quote);
        $plugin->beforeSaveAddressInformation(
            new Subject(),
            1,
            $this->buildAddressInfo($extAttributes)
        );
    }

    public function test_beforeSaveAddressInformation_email_consent_sets_kl_email_consent_on_quote()
    {
        $extAttributes = new class {
            public function getKlSmsConsent(): ?string
            {
                return null;
            }
            public function getKlEmailConsent(): string
            {
                return '1';
            }
        };

        $quote = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['setKlSmsConsent', 'setKlEmailConsent'])
            ->getMock();
        $quote->expects($this->once())
            ->method('setKlSmsConsent')
            ->with(null);
        $quote->expects($this->once())
            ->method('setKlEmailConsent')
            ->with('1');

        $plugin = $this->buildPlugin($quote);
        $plugin->beforeSaveAddressInformation(
            new Subject(),
            1,
            $this->buildAddressInfo($extAttributes)
        );
    }

    public function test_beforeSaveAddressInformation_no_ext_attributes_returns_null()
    {
        $info = $this->getMockBuilder(ShippingInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $info->method('getExtensionAttributes')->willReturn(null);

        $repo = $this->getMockBuilder(QuoteRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->never())->method('getActive');

        $plugin = new ShippingInformationManagement($repo);
        $result = $plugin->beforeSaveAddressInformation(new Subject(), 1, $info);
        $this->assertNull($result);
    }
}
