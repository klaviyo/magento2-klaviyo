<?php

namespace Magento\Framework\Setup;

if (!interface_exists(\Magento\Framework\Setup\ModuleDataSetupInterface::class, false)) {
    interface ModuleDataSetupInterface
    {
        public function getConnection();
        public function getTable($tableName);
        public function startSetup();
        public function endSetup();
    }
}
