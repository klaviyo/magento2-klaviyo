<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Model\Config\Source\MobileChannels;

class MobileChannelsTest extends TestCase
{
    /**
     * @var MobileChannels
     */
    protected $mobileChannels;

    protected function setUp(): void
    {
        $this->mobileChannels = new MobileChannels();
    }

    public function test_toOptionArray_returns_exactly_two_entries()
    {
        $result = $this->mobileChannels->toOptionArray();
        $this->assertCount(2, $result);
    }

    public function test_toOptionArray_first_entry_has_sms_value()
    {
        $result = $this->mobileChannels->toOptionArray();
        $values = array_column($result, 'value');
        $this->assertContains('sms', $values);
    }

    public function test_toOptionArray_second_entry_has_whatsapp_value()
    {
        $result = $this->mobileChannels->toOptionArray();
        $values = array_column($result, 'value');
        $this->assertContains('whatsapp', $values);
    }

    public function test_toOptionArray_implements_array_interface()
    {
        $this->assertInstanceOf(\Magento\Framework\Option\ArrayInterface::class, $this->mobileChannels);
    }
}
