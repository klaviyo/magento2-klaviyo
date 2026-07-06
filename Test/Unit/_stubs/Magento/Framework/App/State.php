<?php

namespace Magento\Framework\App;

if (!class_exists(\Magento\Framework\App\State::class, false)) {
    class State
    {
        protected $areaCode;

        public function getMode()
        {
            return null;
        }

        public function setAreaCode($code)
        {
            $this->areaCode = $code;
        }

        public function getAreaCode()
        {
            return $this->areaCode;
        }
    }
}
