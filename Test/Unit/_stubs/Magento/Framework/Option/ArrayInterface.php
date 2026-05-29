<?php

namespace Magento\Framework\Option;

if (!interface_exists(\Magento\Framework\Option\ArrayInterface::class, false)) {
    interface ArrayInterface
    {
        public function toOptionArray();
    }
}
