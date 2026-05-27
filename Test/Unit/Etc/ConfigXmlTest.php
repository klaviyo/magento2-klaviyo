<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\Etc;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class ConfigXmlTest extends TestCase
{
    /** @var SimpleXMLElement */
    private $xml;

    protected function setUp(): void
    {
        $path = dirname(__DIR__, 3) . '/etc/config.xml';
        $this->xml = new SimpleXMLElement(file_get_contents($path));
    }

    public function test_config_xml_contains_no_sms_consent_references()
    {
        $content = file_get_contents(dirname(__DIR__, 3) . '/etc/config.xml');
        $this->assertStringNotContainsString(
            'sms_consent',
            $content,
            'etc/config.xml must not reference sms_consent after migration to mobile_consent'
        );
    }

    public function test_config_xml_mobile_consent_label_text_default_exists_and_is_non_empty()
    {
        $nodes = $this->xml->xpath(
            '//default/klaviyo_reclaim_consent_at_checkout/mobile_consent/label_text'
        );
        $this->assertNotEmpty($nodes, 'mobile_consent/label_text default must exist in config.xml');
        $this->assertNotSame('', trim((string) $nodes[0]), 'mobile_consent/label_text default must be non-empty');
    }

    public function test_config_xml_mobile_consent_consent_text_default_exists_and_is_non_empty()
    {
        $nodes = $this->xml->xpath(
            '//default/klaviyo_reclaim_consent_at_checkout/mobile_consent/consent_text'
        );
        $this->assertNotEmpty($nodes, 'mobile_consent/consent_text default must exist in config.xml');
        $this->assertNotSame('', trim((string) $nodes[0]), 'mobile_consent/consent_text default must be non-empty');
    }

    public function test_config_xml_mobile_consent_channels_default_is_sms_and_whatsapp()
    {
        $nodes = $this->xml->xpath(
            '//default/klaviyo_reclaim_consent_at_checkout/mobile_consent/channels'
        );
        $this->assertNotEmpty($nodes, 'mobile_consent/channels default must exist in config.xml');
        $this->assertSame('sms,whatsapp', (string) $nodes[0]);
    }

    public function test_config_xml_mobile_consent_sort_order_default_is_200()
    {
        $nodes = $this->xml->xpath(
            '//default/klaviyo_reclaim_consent_at_checkout/mobile_consent/sort_order'
        );
        $this->assertNotEmpty($nodes, 'mobile_consent/sort_order default must exist in config.xml');
        $this->assertSame('200', (string) $nodes[0]);
    }
}
