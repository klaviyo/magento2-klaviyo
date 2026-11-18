<?php

namespace Magento\Framework\App\Helper;

if (!class_exists(\Magento\Framework\App\Helper\AbstractHelper::class, false)) {
    abstract class AbstractHelper
    {
        protected $_context;

        public function __construct($context = null)
        {
            $this->_context = $context;
        }
    }
}
