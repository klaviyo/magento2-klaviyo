<?php

namespace Magento\Framework\App\Config\Storage;

require_once __DIR__ . '/../ScopeConfigInterface.php';

if (!interface_exists(\Magento\Framework\App\Config\Storage\WriterInterface::class, false)) {
    interface WriterInterface
    {
        public function delete(
            $path,
            $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId = 0
        );

        public function save(
            $path,
            $value,
            $scope = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $scopeId = 0
        );
    }
}
