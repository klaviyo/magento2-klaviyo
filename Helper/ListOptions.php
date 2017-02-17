<?php

namespace Klaviyo\Reclaim\Helper;

class ListOptions implements \Magento\Framework\Option\ArrayInterface
{

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Klaviyo\Reclaim\Helper\Data $data_helper
    ) {
        $this->messageManager = $messageManager;
        $this->data_helper = $data_helper;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        // This is a bit hacky. We need to ultimately provide the reason to the custom
        // field in Klaviyo\Reclaim\Block\System\Config\Form\Field\Newsletter, so we pass
        // it over in the options array.

        if (!$this->data_helper->getPrivateApiKey()) {
            return [[
                'label' => 'To sync newsletter subscribers to Klaviyo, first save a <strong>Private Klaviyo API Key</strong> on the "General" tab.',
                'value' => 0
            ]];
        }

        $result = $this->data_helper->getKlaviyoLists();
        if (!$result['success']) {
            return [[
                'label' => $result['reason'] . ' To sync newsletter subscribers to Klaviyo, update the <strong>Private Klaviyo API Key</strong> on the "General" tab.',
                'value' => 0
            ]];
        }

        if (!count($result['lists'])) {
            return [[
                'label' => 'You don\\\'t have any Klaviyo lists. Please create one first at <a href="https://www.klaviyo.com/lists/create" target="_blank">https://www.klaviyo.com/lists/create</a> and then return here to select it.',
                'value' => 0
            ]];
        }

        $options = array_map(function($list) {
            return ['label' => $list->name, 'value' => $list->id];
        }, $result['lists']);

        $default_value = [
            'label' => 'Select a list...',
            'value' => 0
        ];
        array_unshift($options, $default_value);

        return $options;
    }
}