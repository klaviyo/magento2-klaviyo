<?php

declare(strict_types=1);

namespace Klaviyo\Reclaim\Test\Unit\View\Adminhtml;

use PHPUnit\Framework\TestCase;

class MobileConsentLayoutTest extends TestCase
{
    /** @var string */
    private $repoRoot;

    /** @var string */
    private $layoutPath;

    /** @var string */
    private $templatePath;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__, 4);
        $this->layoutPath = $this->repoRoot . '/view/adminhtml/layout/adminhtml_system_config_edit.xml';
        $this->templatePath = $this->repoRoot . '/view/adminhtml/templates/adminhtml/mobile-consent-form.phtml';
    }

    public function test_layout_xml_file_exists(): void
    {
        $this->assertFileExists(
            $this->layoutPath,
            'view/adminhtml/layout/adminhtml_system_config_edit.xml must exist'
        );
    }

    public function test_layout_xml_references_mobile_consent_js(): void
    {
        $this->assertFileExists($this->layoutPath);
        $content = file_get_contents($this->layoutPath);
        $this->assertStringContainsString(
            'mobile-consent-form',
            $content,
            'Layout XML must reference mobile-consent-form JS/template'
        );
    }

    public function test_layout_xml_is_valid_xml(): void
    {
        $this->assertFileExists($this->layoutPath);
        $content = file_get_contents($this->layoutPath);
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        $this->assertNotFalse($doc, 'adminhtml_system_config_edit.xml must be valid XML');
        $this->assertEmpty($errors, 'adminhtml_system_config_edit.xml must have no XML parse errors');
    }

    public function test_template_file_exists(): void
    {
        $this->assertFileExists(
            $this->templatePath,
            'view/adminhtml/templates/adminhtml/mobile-consent-form.phtml must exist'
        );
    }

    public function test_template_requires_mobile_consent_form_js_module(): void
    {
        $this->assertFileExists($this->templatePath);
        $content = file_get_contents($this->templatePath);
        $this->assertStringContainsString(
            'Klaviyo_Reclaim/js/mobile-consent-form',
            $content,
            'Template must require Klaviyo_Reclaim/js/mobile-consent-form'
        );
    }

    public function test_template_calls_mobile_consent_form_init(): void
    {
        $this->assertFileExists($this->templatePath);
        $content = file_get_contents($this->templatePath);
        $this->assertStringContainsString(
            'mobileConsentForm.init',
            $content,
            'Template must call mobileConsentForm.init()'
        );
    }

    public function test_template_has_no_php_syntax_errors(): void
    {
        $this->assertFileExists($this->templatePath);
        $output = shell_exec('php -l ' . escapeshellarg($this->templatePath) . ' 2>&1');
        $this->assertStringContainsString(
            'No syntax errors',
            (string) $output,
            'Template phtml must have no PHP syntax errors'
        );
    }
}
