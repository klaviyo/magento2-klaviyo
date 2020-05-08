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
    protected $object;

    protected function setUp()
    {
        $contextMock = $this->createMock(Context::class);

        $this->object = new Newsletter(
            $contextMock
        );
    }

    public function testNewsletterInstance()
    {
        $this->assertInstanceOf(Newsletter::class, $this->object);
    }
    
    // /**
    //  * Not sure if it is possible to test this method since it is protected
    //  * I think we have to call one of the public methods defined in a parent
    //  */
    // public function test__GetElementHtml()
    // {
    //
    // }
}