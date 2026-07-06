<?php

namespace Magento\Framework;

if (!class_exists(\Magento\Framework\Registry::class, false)) {
    class Registry
    {
        protected $registry = [];

        public function registry($key)
        {
            return $this->registry[$key] ?? null;
        }

        public function register($key, $value, $graceful = false)
        {
            $this->registry[$key] = $value;
        }

        public function unregister($key)
        {
            unset($this->registry[$key]);
        }
    }
}
