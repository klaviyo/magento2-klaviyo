<?php

namespace Klaviyo\Reclaim\Model\Config\Source;

class Radiobtn implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return
        [
            [
                'value' => true,
                'label' => __('Yes, use the Klaviyo settings for this list')
            ],
            [
                'value' => false,
                'label' => __('No, do not send opt-in emails from Klaviyo')
            ],
        ];
    }
}
