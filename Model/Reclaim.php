<?php
namespace Klaviyo\Reclaim\Model;
use Klaviyo\Reclaim\Api\ReclaimInterface;

class Reclaim implements ReclaimInterface
{
    /**
     * Returns all stores with extended descriptions
     *
     * @api
     * @return JSON
     */
    public function stores()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $store_manager = $object_manager->get('\Magento\Store\Model\StoreManagerInterface');
        $stores = $store_manager->getStores();

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

        return $hydrated_stores;
    }
}
