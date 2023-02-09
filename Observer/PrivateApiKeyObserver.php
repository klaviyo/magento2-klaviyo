<?php

namespace Klaviyo\Reclaim\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class PrivateApiKeyObserver implements ObserverInterface
{
    protected $messageManager;
    protected $_dataHelper;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Klaviyo\Reclaim\Helper\Data $_dataHelper
    ) {
        $this->messageManager = $messageManager;
        $this->_dataHelper = $_dataHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $field = $observer->getEvent()['config_data']->getData();

        if (!array_key_exists('field', $field) || $field['field'] !== 'private_api_key') {
            return;
        }

        $api_key = $field['value'];
        if (!$api_key) {
            return;
        }

        $result = $this->_dataHelper->getKlaviyoLists($api_key);

        if ($result['success']) {
            $this->messageManager->addSuccessMessage('Your Private Klaviyo API Key was successfully validated.');
        } else {
            $this->messageManager->addErrorMessage($result['reason']);
        }
    }
}
