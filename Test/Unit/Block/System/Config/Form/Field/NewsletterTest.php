<?php
namespace Klaviyo\Reclaim\Test\Unit\Block\System\Config\Form\Field;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Block\System\Config\Form\Field\Newsletter;
use Magento\Framework\Data\Form\Element\AbstractElement;

class NewsletterTest extends TestCase
{
    /**
     * @var Newsletter
     */
    protected $object;

    // protected function setUp()
    // {
    //     $mockElement = $this->getMock(AbstractElement::class);
    //     $mockElementValues = [['label'=>'TestValue']];
    //     $mockElement->method('getValues')->willReturn($mockElementValues);

    //     $this->object = $this->getMock();
    // }

    // /**
    //  * Not sure if it is possible to test this method since it is protected
    //  * I think we have to call one of the public methods defined in its parent
    //  */
    // public function test__GetElementHtml()
    // {
    //     $expectedOutput = [];
    //     $this->assertSame($expectedOutput, $this->object->__getElementHteml($mockElement));
    // }
}