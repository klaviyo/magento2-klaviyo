<?php

namespace Klaviyo\Reclaim\Controller\Store;

class List extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->request = $context->getRequest();
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
    /**
     * Enpoint /reclaim/store/list resolves here. This endpoint is used indirectly
     * via sections.xml so that the minicart can be updated on the client side on request
     *
     * @return JSON
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $stores = Mage::app()->getStores();

        $hydrated_stores = array();
        foreach ($stores as $store)
        {
            $store_id = $store->getId();
            $store_website_id = $store->getWebsiteId();
            $store_name = $store->getName();
            $store_code = $store->getCode();
            $base_url = $store->getBaseUrl();
            $media_base_url = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            array_push($hydrated_stores, array(
              'id' => $store_id,
              'website_id' => $store_website_id,
              'name' => $store_name,
              'code' => $store_code,
              'base_url' => $base_url,
              'media_base_url' => $media_base_url,
            ));
        }

        return $result->setData($hydrated_stores);
    }
}
