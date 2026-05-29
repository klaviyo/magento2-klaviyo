<?php

namespace Magento\Framework\Setup\Patch;

if (!interface_exists(\Magento\Framework\Setup\Patch\DataPatchInterface::class, false)) {
    interface DataPatchInterface
    {
        public function apply();
        public function getAliases();
        public static function getDependencies();
    }
}
