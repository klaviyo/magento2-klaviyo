<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use \Klaviyo\Reclaim\Helper\Logger;

class PrivateApiKeyObserver implements ObserverInterface
{
    protected $messageManager;
    protected $_dataHelper;

    protected $_klaviyologger;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Klaviyo\Reclaim\Helper\Data $_dataHelper,
        \Klaviyo\Reclaim\Helper\Logger $_klaviyologger
    ) {
        $this->messageManager = $messageManager;
        $this->_dataHelper = $_dataHelper;
        $this->_klaviyologger = $_klaviyologger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $field = $observer->getEvent()['config_data']->getData();
        $this->_klaviyologger->log("INSIDE API OBSERVER");
        $this->_klaviyologger->log(json_encode($observer->getEvent()['config_data']));
        if (!array_key_exists('field', $field) || $field['field'] !== 'private_api_key') return;

        $api_key = $field['value'];
        if (!$api_key) return;

        $result = $this->_dataHelper->getKlaviyoLists($api_key);

        if ($result['success']) {
            $this->_klaviyologger->log("SUCCESS");
            $this->messageManager->addSuccessMessage('Your Private Klaviyo API Key was successfully validated.');
        } else {
            $this->_klaviyologger->log("FAIL");
            $this->messageManager->addErrorMessage($result['reason']);
        }
    }
}