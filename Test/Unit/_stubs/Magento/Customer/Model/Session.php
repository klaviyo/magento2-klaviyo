<?php

namespace Magento\Customer\Model;

if (!class_exists(\Magento\Customer\Model\Session::class, false)) {
    class Session
    {
        public function isLoggedIn(): bool
        {
            return false;
        }
        public function getCustomer()
        {
            return null;
        }
    }
}
