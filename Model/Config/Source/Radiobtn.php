<?php
namespace Vendor\Extension\Model\Config\Source;
class Radiobtn implements \Magento\Framework\Option\ArrayInterface 
{
    public function toOptionArray()
    { 
        return 
        [
            [
                'value' => true, 
                'label' => __('Yes, I want Klaviyo to handle the double opt-in stuff')
            ], 
            [
                'value' => false, 
                'label' => __('No, I will do this through Magento\'s built-in stuff')
            ],
        ];
	}
}