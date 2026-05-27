<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\Util;

use Klaviyo\Reclaim\Util\PhoneFormatter;
use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    /** @var PhoneFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->formatter = new PhoneFormatter();
    }

    public function test_formatE164_us_national_number_returns_e164(): void
    {
        $this->assertSame('+12025550100', $this->formatter->formatE164('2025550100', 'US'));
    }

    public function test_formatE164_us_punctuated_number_returns_e164(): void
    {
        $this->assertSame('+12025550100', $this->formatter->formatE164('(202) 555-0100', 'US'));
    }

    public function test_formatE164_already_e164_returns_e164_unchanged(): void
    {
        $this->assertSame('+12025550100', $this->formatter->formatE164('+1 (202) 555-0100', 'US'));
    }

    public function test_formatE164_uk_national_number_returns_e164(): void
    {
        $this->assertSame('+442079460958', $this->formatter->formatE164('020 7946 0958', 'GB'));
    }

    public function test_formatE164_empty_phone_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164('', 'US'));
    }

    public function test_formatE164_null_phone_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164(null, 'US'));
    }

    public function test_formatE164_empty_country_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164('2025550100', ''));
    }

    public function test_formatE164_null_country_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164('2025550100', null));
    }

    public function test_formatE164_unparseable_input_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164('not-a-number', 'US'));
    }

    public function test_formatE164_too_short_number_returns_null(): void
    {
        $this->assertNull($this->formatter->formatE164('123', 'US'));
    }
}
