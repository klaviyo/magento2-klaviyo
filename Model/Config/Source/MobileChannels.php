<?php

namespace Klaviyo\Reclaim\Model\Config\Source;

class MobileChannels implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'sms', 'label' => __('Text message')],
            ['value' => 'whatsapp', 'label' => __('WhatsApp')],
        ];
    }
}
