<?php

namespace Magento\Framework\Controller\Result;

if (!class_exists(\Magento\Framework\Controller\Result\Redirect::class, false)) {
    class Redirect
    {
        public function setPath($path, array $params = [])
        {
            return $this;
        }
    }
}
