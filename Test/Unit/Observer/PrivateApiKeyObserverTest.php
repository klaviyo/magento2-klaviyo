<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Test\Data\SampleExtension;
use Klaviyo\Reclaim\Observer\PrivateApiKeyObserver;
use Klaviyo\Reclaim\Helper\Data;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;

class PrivateApiKeyObserverTest extends TestCase
{
    /**
     * @var PrivateApiKeyObserver
     */
    protected $privateApiKeyObserver;

    const SUCCESS_MESSAGE = 'Your Private Klaviyo API Key was successfully validated.';
    const FIELD_NAME = 'private_api_key';

    protected function setUp(): void
    {
        $messageManagerMock = $this->createMock(MessageManager::class);
        $messageManagerMock->method('addSuccessMessage')
            ->with(self::SUCCESS_MESSAGE)
            ->willReturn(true);

        $dataMock = $this->createMock(Data::class);
        $dataMock->method('getKlaviyoLists')
            ->with($this->equalTo(SampleExtension::PRIVATE_API_KEY))
            ->willReturn(['success' => true]);

        $this->privateApiKeyObserver = new PrivateApiKeyObserver(
            $messageManagerMock,
            $dataMock
        );
    }

    public function testPrivateApiKeyObserverInstance()
    {
        $this->assertInstanceOf(PrivateApiKeyObserver::class, $this->privateApiKeyObserver);
    }

    public function testExecute()
    {
        $mockDataObject = $this->createMock(DataObject::class);
        $mockDataObject->method('getData')->willReturn(
            [
                'field' => self::FIELD_NAME,
                'value' => SampleExtension::PRIVATE_API_KEY
            ]
        );
        $eventMock = ['config_data' => $mockDataObject];
        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')->willReturn($eventMock);

        $didNotFail = true;

        try {
            $this->privateApiKeyObserver->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = false;
        }

        $this->assertTrue($didNotFail);
    }
}
