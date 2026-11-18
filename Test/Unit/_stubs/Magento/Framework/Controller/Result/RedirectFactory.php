<?php

namespace Magento\Framework\Controller\Result;

if (!class_exists(\Magento\Framework\Controller\Result\RedirectFactory::class, false)) {
    class RedirectFactory
    {
        public function create(array $data = [])
        {
            return new Redirect();
        }
    }
}
