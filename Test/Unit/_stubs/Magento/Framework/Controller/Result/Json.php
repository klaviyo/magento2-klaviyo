<?php

namespace Magento\Framework\Controller\Result;

if (!class_exists(\Magento\Framework\Controller\Result\Json::class, false)) {
    class Json
    {
        public function setData($data)
        {
            return $this;
        }
    }
}
