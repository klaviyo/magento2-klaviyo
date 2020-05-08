<?php

namespace Klaviyo\Reclaim\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use Klaviyo\Reclaim\Observer\PrivateApiKeyObserver;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Klaviyo\Reclaim\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;

class PrivateApiKeyObserverTest extends TestCase
{
    /**
     * @var PrivateApiKeyObserver
     */
    protected $object;

    const PRIVATE_API_KEY = 'pk_aaaassssddddffff';
    const SUCCESS_MESSAGE = 'Your Private Klaviyo API Key was successfully validated.';
    const FIELD_NAME = 'private_api_key';

    protected function setUp()
    {
        $messageManagerMock = $this->createMock(MessageManager::class);
        $messageManagerMock->method('addSuccessMessage')
            ->with(self::SUCCESS_MESSAGE)
            ->willReturn(TRUE);

        $dataMock = $this->createMock(Data::class);
        $dataMock->method('getKlaviyoLists')
            ->with($this->equalTo(self::PRIVATE_API_KEY))
            ->willReturn(array('success'=>TRUE));

        $this->object = new PrivateApiKeyObserver(
            $messageManagerMock,
            $dataMock
        );
    }

    public function testPrivateApiKeyObserverInstance()
    {
        $this->assertInstanceOf(PrivateApiKeyObserver::class, $this->object);
    }

    public function testExecute()
    {
        $mockDataObject = $this->createMock(DataObject::class);
        $mockDataObject->method('getData')->willReturn(
            array(
                'field' => self::FIELD_NAME,
                'value' => self::PRIVATE_API_KEY
            )
        );
        $eventMock = array('config_data' => $mockDataObject);
        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')->willReturn($eventMock);

        $didNotFail = TRUE;

        try {
            $this->object->execute($observerMock);
        } catch (\Exception $ex) {
            $didNotFail = FALSE;
        }

        $this->assertTrue($didNotFail);

    }
}