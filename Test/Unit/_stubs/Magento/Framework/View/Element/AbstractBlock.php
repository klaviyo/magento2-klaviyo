<?php

namespace Magento\Framework\View\Element;

require_once __DIR__ . '/../../DataObject.php';
require_once __DIR__ . '/BlockInterface.php';
require_once __DIR__ . '/Context.php';

if (!class_exists(\Magento\Framework\View\Element\AbstractBlock::class, false)) {
    abstract class AbstractBlock extends \Magento\Framework\DataObject implements BlockInterface
    {
        protected $_request;
        protected $_layout;
        protected $_eventManager;
        protected $_urlBuilder;
        protected $_cache;
        protected $_design;
        protected $_session;
        protected $_sidResolver;
        protected $_scopeConfig;
        protected $_assetRepo;
        protected $_viewConfig;
        protected $_cacheState;
        protected $_logger;
        protected $_escaper;
        protected $filterManager;
        protected $_localeDate;
        protected $inlineTranslation;
        protected $lockQuery;

        public function __construct(Context $context, array $data = [])
        {
            $this->_request = $context->getRequest();
            $this->_layout = $context->getLayout();
            $this->_eventManager = $context->getEventManager();
            $this->_urlBuilder = $context->getUrlBuilder();
            $this->_cache = $context->getCache();
            $this->_design = $context->getDesignPackage();
            $this->_session = $context->getSession();
            $this->_sidResolver = $context->getSidResolver();
            $this->_scopeConfig = $context->getScopeConfig();
            $this->_assetRepo = $context->getAssetRepository();
            $this->_viewConfig = $context->getViewConfig();
            $this->_cacheState = $context->getCacheState();
            $this->_logger = $context->getLogger();
            $this->_escaper = $context->getEscaper();
            $this->filterManager = $context->getFilterManager();
            $this->_localeDate = $context->getLocaleDate();
            $this->inlineTranslation = $context->getInlineTranslation();
            $this->lockQuery = $context->getLockGuardedCacheLoader();

            parent::__construct($data);
            $this->_construct();
        }

        protected function _construct()
        {
        }

        public function toHtml()
        {
            return '';
        }
    }
}
