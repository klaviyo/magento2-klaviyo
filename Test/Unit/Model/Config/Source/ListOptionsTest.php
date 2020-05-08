<?php

namespace Klaviyo\Reclaim\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Model\Config\Source\ListOptions;
use Magento\Framework\Message\ManagerInterface;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Klaviyo\Reclaim\Helper\Data;

class ListOptionsTest extends TestCase
{
    /**
     * @var ListOptions
     */
    protected $object;

    protected function setUp()
    {
        $messageManagerMock = $this->createMock(ManagerInterface::class);

        $scopeSettingMock = $this->createMock(ScopeSetting::class);
        $scopeSettingMock->method('getPrivateApiKey')->willReturn('QWEasd');

        $dataMock = $this->createMock(Data::class);
        // I know this looks messy and ugly
        // but it's the least terrible way I could come up with to mock this
        $listsMock = json_decode("[{\"name\":\"list1\",\"id\":\"aaAAaa\"},{\"name\":\"list2\",\"id\":\"ssSSss\"},{\"name\":\"list3\",\"id\":\"ddDDdd\"}]");
        $resultMock = array(
            'success' => 'true',
            'lists' => $listsMock
        );
        $dataMock->method('getKlaviyoLists')->willReturn($resultMock);

        $this->object = new ListOptions(
            $messageManagerMock,
            $scopeSettingMock,
            $dataMock
        );
    }

    public function testListOptionsInstance()
    {
        $this->assertInstanceOf(ListOptions::class, $this->object);
    }

    public function testToOptionArray()
    {
        //test when everything goes right
        $expectedResponse = array(
            array(
                ListOptions::LABEL => 'Select a list...',
                ListOptions::VALUE => 0
            ),
            array(
                ListOptions::LABEL => 'list1',
                ListOptions::VALUE => 'aaAAaa'
            ),
            array(
                ListOptions::LABEL => 'list2',
                ListOptions::VALUE => 'ssSSss'
            ),
            array(
                ListOptions::LABEL => 'list3',
                ListOptions::VALUE => 'ddDDdd'
            )
        );
        $this->assertSame($expectedResponse, $this->object->toOptionArray());
    }
}
