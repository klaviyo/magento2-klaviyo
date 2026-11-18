<?php

namespace Magento\Quote\Model\ResourceModel;

if (!class_exists(\Magento\Quote\Model\ResourceModel\Quote::class, false)) {
    class Quote
    {
        public function load($object, $value, $field = null)
        {
            return $this;
        }
    }
}
