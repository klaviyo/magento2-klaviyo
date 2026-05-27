<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\View\Adminhtml;

use PHPUnit\Framework\TestCase;

class MobileConsentFormJsTest extends TestCase
{
    /** @var string */
    private $jsPath;

    /** @var string */
    private $jsContent;

    protected function setUp(): void
    {
        $this->jsPath = dirname(__DIR__, 4) . '/view/adminhtml/web/js/mobile-consent-form.js';
        $this->jsContent = file_exists($this->jsPath) ? file_get_contents($this->jsPath) : '';
    }

    public function test_mobile_consent_form_js_file_exists(): void
    {
        $this->assertFileExists(
            $this->jsPath,
            'view/adminhtml/web/js/mobile-consent-form.js must exist'
        );
    }

    public function test_mobile_consent_form_js_uses_requirejs_define(): void
    {
        $this->assertStringContainsString(
            'define([',
            $this->jsContent,
            'JS must use RequireJS define()'
        );
    }

    public function test_mobile_consent_form_js_has_sms_channel_config(): void
    {
        $this->assertStringContainsString(
            'sms:',
            $this->jsContent,
            'JS must have sms channel config entry'
        );
    }

    public function test_mobile_consent_form_js_has_whatsapp_channel_config(): void
    {
        $this->assertStringContainsString(
            'whatsapp:',
            $this->jsContent,
            'JS must have whatsapp channel config entry'
        );
    }

    public function test_mobile_consent_form_js_has_both_channel_config(): void
    {
        $this->assertStringContainsString(
            'both:',
            $this->jsContent,
            'JS must have both channel config entry'
        );
    }

    public function test_mobile_consent_form_js_has_channels_change_listener(): void
    {
        $this->assertStringContainsString(
            "'change'",
            $this->jsContent,
            'JS must attach a change listener for channels multiselect'
        );
    }

    public function test_mobile_consent_form_js_updates_channels_note(): void
    {
        $this->assertStringContainsString(
            'channelsNote',
            $this->jsContent,
            'JS must update channelsNote helper text'
        );
    }

    public function test_mobile_consent_form_js_updates_consent_note(): void
    {
        $this->assertStringContainsString(
            'consentNote',
            $this->jsContent,
            'JS must update consentNote helper text'
        );
    }

    public function test_mobile_consent_form_js_has_sms_text_message_helper_text(): void
    {
        $this->assertStringContainsString(
            'Text message must be set up',
            $this->jsContent,
            'SMS channelsNote must mention "Text message must be set up"'
        );
    }

    public function test_mobile_consent_form_js_auto_populates_label_default(): void
    {
        $this->assertStringContainsString(
            'labelDefault',
            $this->jsContent,
            'JS must have labelDefault per-channel for auto-populate'
        );
    }

    public function test_mobile_consent_form_js_auto_populates_consent_default(): void
    {
        $this->assertStringContainsString(
            'consentDefault',
            $this->jsContent,
            'JS must have consentDefault per-channel for auto-populate'
        );
    }
}
