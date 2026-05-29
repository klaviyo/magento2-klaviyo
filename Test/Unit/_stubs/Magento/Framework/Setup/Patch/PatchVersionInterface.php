<?php

namespace Magento\Framework\Setup\Patch;

if (!interface_exists(\Magento\Framework\Setup\Patch\PatchVersionInterface::class, false)) {
    interface PatchVersionInterface
    {
        public static function getVersion();
    }
}
