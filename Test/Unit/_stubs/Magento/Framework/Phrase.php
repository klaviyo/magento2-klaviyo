<?php

namespace Magento\Framework;

if (!class_exists(\Magento\Framework\Phrase::class, false)) {
    class Phrase
    {
        private $text;
        private $args;

        public function __construct($text, array $args = [])
        {
            $this->text = $text;
            $this->args = $args;
        }

        public function render()
        {
            return $this->args ? vsprintf($this->text, $this->args) : $this->text;
        }

        public function __toString()
        {
            return $this->render();
        }
    }
}
