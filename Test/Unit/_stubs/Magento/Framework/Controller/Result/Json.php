<?php

namespace Magento\Framework\Controller\Result;

if (!class_exists(\Magento\Framework\Controller\Result\Json::class)) {
    class Json
    {
        public function setData($data)
        {
            return $this;
        }
    }
}
