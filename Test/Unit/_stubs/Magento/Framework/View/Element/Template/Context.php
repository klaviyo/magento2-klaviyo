<?php

namespace Magento\Framework\View\Element\Template;

require_once __DIR__ . '/../Context.php';

if (!class_exists(\Magento\Framework\View\Element\Template\Context::class, false)) {
    class Context extends \Magento\Framework\View\Element\Context
    {
        public function getResolver()
        {
            return null;
        }

        public function getValidator()
        {
            return null;
        }

        public function getFilesystem()
        {
            return null;
        }

        public function getEnginePool()
        {
            return null;
        }

        public function getStoreManager()
        {
            return null;
        }

        public function getAppState()
        {
            return null;
        }

        public function getPageConfig()
        {
            return null;
        }
    }
}
