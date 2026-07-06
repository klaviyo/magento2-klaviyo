<?php

namespace Magento\Framework\App;

if (!interface_exists(\Magento\Framework\App\RequestInterface::class, false)) {
    interface RequestInterface
    {
        public function getModuleName();

        public function setModuleName($name);

        public function getActionName();

        public function setActionName($name);

        public function getParam($key, $defaultValue = null);

        public function setParams(array $params);

        public function getParams();

        public function isSecure();
    }
}
