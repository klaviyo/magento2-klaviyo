<?php

namespace Klaviyo\Reclaim\Test\Unit\Block\System\Config\Form\Field;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\System\Config\Form\Field\Newsletter;

class NewsletterTest extends TestCase
{
    /**
     * @var Newsletter
     */
    protected $newsletter;

    protected function setUp(): void
    {
        $this->newsletter = $this->getMockBuilder(Newsletter::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testNewsletterInstance()
    {
        $this->assertInstanceOf(Newsletter::class, $this->newsletter);
    }
}
