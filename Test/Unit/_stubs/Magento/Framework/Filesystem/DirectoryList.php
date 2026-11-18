<?php

namespace Magento\Framework\Filesystem;

if (!class_exists(\Magento\Framework\Filesystem\DirectoryList::class, false)) {
    class DirectoryList
    {
        private $root;
        private $config;

        public function __construct($root, array $config = [])
        {
            $this->root = $root;
            $this->config = $config;
        }

        public function getPath($code)
        {
            return $this->config[$code]['path'] ?? '';
        }

        public function getRoot()
        {
            return $this->root;
        }
    }
}
