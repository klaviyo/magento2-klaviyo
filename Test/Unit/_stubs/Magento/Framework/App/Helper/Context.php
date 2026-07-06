<?php

namespace Magento\Framework\App\Helper;

if (!class_exists(\Magento\Framework\App\Helper\Context::class, false)) {
    class Context
    {
        public function getModuleManager()
        {
            return null;
        }

        public function getUrlBuilder()
        {
            return null;
        }

        public function getRequest()
        {
            return null;
        }

        public function getEventManager()
        {
            return null;
        }

        public function getLogger()
        {
            return null;
        }

        public function getRemoteAddress()
        {
            return null;
        }

        public function getScopeConfig()
        {
            return null;
        }
    }
}
