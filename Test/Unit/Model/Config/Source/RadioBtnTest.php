<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Fakes\RadioBtnFake as RadioBtn;

class RadioBtnTest extends TestCase
{
    /**
     * @var RadioBtn
     */
    protected $radioBtn;

    protected function setUp(): void
    {
        $this->radioBtn = new RadioBtn();
    }

    public function testToOptionArray()
    {
        $expectedResponse = [
            [
                'value' => true,
                'label' => 'Yes, use the Klaviyo settings for this list'
            ],
            [
                'value' => false,
                'label' => 'No, do not send opt-in emails from Klaviyo'
            ],
        ];
        $this->assertSame($expectedResponse, $this->radioBtn->toOptionArray());
    }
}
