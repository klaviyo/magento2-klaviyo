<?php

namespace Magento\Framework\View\Element;

require_once __DIR__ . '/AbstractBlock.php';
require_once __DIR__ . '/Template/Context.php';

if (!class_exists(\Magento\Framework\View\Element\Template::class, false)) {
    class Template extends AbstractBlock
    {
        protected $_storeManager;

        public function __construct(Template\Context $context, array $data = [])
        {
            $this->_storeManager = $context->getStoreManager();
            parent::__construct($context, $data);
        }
    }
}
