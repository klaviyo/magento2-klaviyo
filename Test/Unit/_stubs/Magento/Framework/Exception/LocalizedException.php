<?php

namespace Magento\Framework\Exception;

require_once __DIR__ . '/../Phrase.php';

if (!class_exists(\Magento\Framework\Exception\LocalizedException::class, false)) {
    class LocalizedException extends \Exception
    {
        private $phrase;

        public function __construct(\Magento\Framework\Phrase $phrase, ?\Throwable $cause = null, $code = 0)
        {
            $this->phrase = $phrase;
            parent::__construct((string) $phrase, $code, $cause);
        }

        public function getRawMessage()
        {
            return $this->phrase->render();
        }
    }
}
