<?php

namespace Magento\Framework\Message;

if (!interface_exists(\Magento\Framework\Message\ManagerInterface::class, false)) {
    interface ManagerInterface
    {
        public function getMessages($clear = false, $group = null);

        public function getDefaultGroup();

        public function addError($message, $group = null);

        public function addWarning($message, $group = null);

        public function addNotice($message, $group = null);

        public function addSuccess($message, $group = null);

        public function addErrorMessage($message, $group = null);

        public function addWarningMessage($message, $group = null);

        public function addNoticeMessage($message, $group = null);

        public function addSuccessMessage($message, $group = null);

        public function addException(\Exception $exception, $alternativeText = null, $group = null);

        public function addExceptionMessage(\Exception $exception, $alternativeText = null, $group = null);
    }
}
