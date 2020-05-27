<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Test\Data\SampleListApiResponse;
use Klaviyo\Reclaim\Model\Config\Source\ListOptions;
use Magento\Framework\Message\ManagerInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Data;

class ListOptionsTest extends TestCase
{
    /**
     * @var ListOptions
     */
    protected $listOptions;

    protected function setUp()
    {
        $messageManagerMock = $this->createMock(ManagerInterface::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPrivateApiKey')->willReturn(SampleExtension::PRIVATE_API_KEY);

        $dataMock = $this->createMock(Data::class);
        $listsMock = [
            new SampleListApiResponse('list1', 'aaAAaa'),
            new SampleListApiResponse('list2', 'ssSSss'),
            new SampleListApiResponse('list3', 'ddDDdd')
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
                ListOptions::LABEL => 'list1',
                ListOptions::VALUE => 'aaAAaa'
            ],
            [
                ListOptions::LABEL => 'list2',
                ListOptions::VALUE => 'ssSSss'
            ],
            [
                ListOptions::LABEL => 'list3',
                ListOptions::VALUE => 'ddDDdd'
            ]
        ];
        $this->assertSame($expectedResponse, $this->listOptions->toOptionArray());
    }
}
