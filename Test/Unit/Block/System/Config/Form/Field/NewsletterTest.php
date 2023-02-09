<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\System\Config\Form\Field;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\System\Config\Form\Field\Newsletter;
use Magento\Backend\Block\Template\Context;

class NewsletterTest extends TestCase
{
    /**
     * @var Newsletter
     */
    protected $newsletter;

    protected function setUp(): void
    {
        $contextMock = $this->createMock(Context::class);

        $this->newsletter = new Newsletter(
            $contextMock
        );
    }

    public function testNewsletterInstance()
    {
        $this->assertInstanceOf(Newsletter::class, $this->newsletter);
    }
}
