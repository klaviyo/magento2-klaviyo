<?php

namespace Klaviyo\Reclaim\Helper;

use Magento\Catalog\Model\CategoryFactory;

class CategoryMapper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Magento Category Factory
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * Category Map of ids to names
     * @var array
     */
    protected $categoryMap = [];

    /**
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        CategoryFactory $categoryFactory
    ){
        $this->_categoryFactory = $categoryFactory;
    }

    /**
     * Replace all CategoryIds in payload with their respective names
     * @param $payload
     * @return array
     */
    public function replaceCategoryIdsWithNames(array $payload): array
    {
        $this->updateCategoryMap($payload['Categories']);

        foreach ($payload['Items'] as &$item){
            $item['Categories'] = $this->searchCategoryMapAndReturnNames($item['Categories']);
        }

        $payload['AddedItemCategories'] = $this->searchCategoryMapAndReturnNames($payload['AddedItemCategories']);
        $payload['Categories'] = $this->searchCategoryMapAndReturnNames($payload['Categories']);

        return $payload;
    }

    /**
     * Adds all category names in payload to their respective ids
     * @param $payload json encoded string of the payload
     * @return array
     */
    public function addCategoryNames(string $payload): array
    {
        $decoded_payload = json_decode($payload, true);
        $this->updateCategoryMap($decoded_payload['product']['Categories']);

        $decoded_payload['product']['Categories'] = $this->
          searchCategoryMapAndReturnIdsAndNames(
            $decoded_payload['product']['Categories']
          );

        return $decoded_payload;
    }

    /**
     * Retrieve categoryNames using categoryIds
     * @param array $categoryIds
     */
    public function updateCategoryMap(array $categoryIds)
    {
        $categoryFactory = $this->_categoryFactory->create();

        foreach($categoryIds as $categoryId){
            if (!in_array($categoryId, $this->categoryMap)){
                $this->categoryMap[$categoryId] = $categoryFactory->load($categoryId)->getName();
            }
        }
    }

    /**
     * Return array of CategoryNames from CategoryMap using ids
     * @param array $categoryIds
     * @return array
     */
    public function searchCategoryMapAndReturnNames(array $categoryIds): array
    {
        return array_values(
            array_intersect_key($this->categoryMap, array_flip($categoryIds))
        );
    }

    /**
     * Return array of arrays mapping category ids to their names
     * @param array $categoryIds
     * @return array
     */
    public function searchCategoryMapAndReturnIdsAndNames(array $categoryIds): array
    {
        $categoryIdsAndNames = [];
        foreach ($categoryIds as $categoryId){
          $categoryIdsAndNames[$categoryId] = $this->categoryMap[$categoryId];
        }
        return $categoryIdsAndNames;
    }
}
