<?php

namespace Klaviyo\Reclaim\Plugin\Api;

use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class AddProductRelationships
{
    protected $configurableProductType;

    public function __construct(
        Configurable $configurableProductType
    ) {
        $this->configurableProductType = $configurableProductType;
    }

    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $entity
    ) {
        $extensionAttributes = $entity->getExtensionAttributes();
        $parentIds = $this->configurableProductType->getParentIdsByChild($entity->getId());
        $extensionAttributes->setData('kl_parent_ids', $parentIds);
        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchResults
    ) {
        $products = [];
        foreach ($searchResults->getItems() as $entity) {
            $extensionAttributes = $entity->getExtensionAttributes();
            $parentIds = $this->configurableProductType->getParentIdsByChild($entity->getId());
            $extensionAttributes->setData('kl_parent_ids', $parentIds);
            $entity->setExtensionAttributes($extensionAttributes);

            $products[] = $entity;
        }
        $searchResults->setItems($products);
        return $searchResults;
    }
}
