<?php

namespace Magento\Framework\View\Element;

if (!class_exists(\Magento\Framework\View\Element\Context::class, false)) {
    class Context
    {
        public function getRequest()
        {
            return null;
        }

        public function getLayout()
        {
            return null;
        }

        public function getEventManager()
        {
            return null;
        }

        public function getUrlBuilder()
        {
            return null;
        }

        public function getCache()
        {
            return null;
        }

        public function getDesignPackage()
        {
            return null;
        }

        public function getSession()
        {
            return null;
        }

        public function getSidResolver()
        {
            return null;
        }

        public function getScopeConfig()
        {
            return null;
        }

        public function getAssetRepository()
        {
            return null;
        }

        public function getViewConfig()
        {
            return null;
        }

        public function getCacheState()
        {
            return null;
        }

        public function getLogger()
        {
            return null;
        }

        public function getEscaper()
        {
            return null;
        }

        public function getFilterManager()
        {
            return null;
        }

        public function getLocaleDate()
        {
            return null;
        }

        public function getInlineTranslation()
        {
            return null;
        }

        public function getLockGuardedCacheLoader()
        {
            return null;
        }
    }
}
