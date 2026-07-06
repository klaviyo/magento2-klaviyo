<?php

namespace Magento\Framework\View\Element;

if (!interface_exists(\Magento\Framework\View\Element\BlockInterface::class, false)) {
    interface BlockInterface
    {
        public function toHtml();
    }
}
