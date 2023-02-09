<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleListApiResponse;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Model\Config\Source\ListOptions;
use Magento\Framework\Message\ManagerInterface;

class ListOptionsTest extends TestCase
{
    /**
     * @var ListOptions
     */
    protected $listOptions;

    const LIST1_ID = 'aaAAaa';
    const LIST1_NAME = 'list1';
    const LIST2_ID = 'ssSSss';
    const LIST2_NAME = 'list2';
    const LIST3_ID = 'ddDDdd';
    const LIST3_NAME = 'list3';

    protected function setUp(): void
    {
        $messageManagerMock = $this->createMock(ManagerInterface::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPrivateApiKey')->willReturn(SampleExtension::PRIVATE_API_KEY);

        $dataMock = $this->createMock(Data::class);
        $listsMock = [
            new SampleListApiResponse(self::LIST1_NAME, self::LIST1_ID),
            new SampleListApiResponse(self::LIST2_NAME, self::LIST2_ID),
            new SampleListApiResponse(self::LIST3_NAME, self::LIST3_ID)
        ];
        $resultMock = [
            'success' => 'true',
            'lists' => $listsMock
        ];
        $dataMock->method('getKlaviyoLists')->willReturn($resultMock);

        $this->listOptions = new ListOptions(
            $messageManagerMock,
            $scopeSettingMock,
            $dataMock
        );
    }

    public function testListOptionsInstance()
    {
        $this->assertInstanceOf(ListOptions::class, $this->listOptions);
    }

    public function testToOptionArray()
    {
        //test when everything goes right
        $expectedResponse = [
            [
                ListOptions::LABEL => 'Select a list...',
                ListOptions::VALUE => 0
            ],
            [
                ListOptions::LABEL => self::LIST1_NAME,
                ListOptions::VALUE => self::LIST1_ID
            ],
            [
                ListOptions::LABEL => self::LIST2_NAME,
                ListOptions::VALUE => self::LIST2_ID
            ],
            [
                ListOptions::LABEL => self::LIST3_NAME,
                ListOptions::VALUE => self::LIST3_ID
            ]
        ];
        $this->assertSame($expectedResponse, $this->listOptions->toOptionArray());
    }
}
