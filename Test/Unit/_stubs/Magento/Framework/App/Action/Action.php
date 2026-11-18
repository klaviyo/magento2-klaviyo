<?php

namespace Magento\Framework\App\Action;

if (!class_exists(\Magento\Framework\App\Action\Action::class, false)) {
    class Action
    {
        protected $_objectManager;

        public function getRequest()
        {
            return null;
        }
    }
}
