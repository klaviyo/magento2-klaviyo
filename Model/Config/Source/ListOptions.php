<?php

namespace Klaviyo\Reclaim\Model\Config\Source;

class ListOptions implements \Magento\Framework\Option\ArrayInterface
{
    const LABEL = 'label';
    const VALUE = 'value';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * @var \Klaviyo\Reclaim\Helper\Data
     */
    protected $_dataHelper;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Klaviyo\Reclaim\Helper\ScopeSetting $_klaviyoScopeSetting,
        \Klaviyo\Reclaim\Helper\Data $_dataHelper
    ) {
        $this->messageManager = $messageManager;
        $this->_klaviyoScopeSetting = $_klaviyoScopeSetting;
        $this->_dataHelper = $_dataHelper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        // This is a bit hacky. We need to ultimately provide the reason to the custom
        // field in Klaviyo\Reclaim\Block\System\Config\Form\Field\Newsletter, so we pass
        // it over in the options array.

        if (!$this->_klaviyoScopeSetting->getPrivateApiKey()) {
            return [[
                self::LABEL => 'To sync newsletter subscribers to Klaviyo, first save a <strong>Private Klaviyo API Key</strong> on the "General" tab.',
                self::VALUE => 0
            ]];
        }

        $result = $this->_dataHelper->getKlaviyoLists();
        if (!$result['success']) {
            return [[
                self::LABEL => $result['reason'] . ' To sync newsletter subscribers to Klaviyo, update the <strong>Private Klaviyo API Key</strong> on the "General" tab.',
                self::VALUE => 0
            ]];
        }

        if (!count($result['lists'])) {
            return [[
                self::LABEL => 'You don\\\'t have any Klaviyo lists. Please create one first at <a href="https://www.klaviyo.com/lists/create" target="_blank">https://www.klaviyo.com/lists/create</a> and then return here to select it.',
                self::VALUE => 0
            ]];
        }

        $options = array_map(function ($list) {
            return [self::LABEL => $list['name'], self::VALUE => $list['id']];
        }, $result['lists']);

        $default_value = [
            self::LABEL => 'Select a list...',
            self::VALUE => 0
        ];
        array_unshift($options, $default_value);

        return $options;
    }
}
