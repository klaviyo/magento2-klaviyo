<?php

namespace Magento\Framework\Module;

if (!interface_exists(\Magento\Framework\Module\ModuleListInterface::class, false)) {
    interface ModuleListInterface
    {
        public function getAll();

        public function getOne($name);

        public function getNames();

        public function has($name);
    }
}
