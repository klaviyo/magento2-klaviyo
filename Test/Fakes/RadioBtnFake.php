<?php

namespace Klaviyo\Reclaim\Test\Fakes;

use Klaviyo\Reclaim\Model\Config\Source\Radiobtn;

class RadioBtnFake extends Radiobtn
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

function __($string)
{
    return $string;
}
