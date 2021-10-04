<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\Products;

use Klaviyo\Reclaim\Model\KlaviyoCollection;

/*
 * Klaviyo Products Collection Class
 *
 * Magento Collections are encapsulating the sets of models and related functionality, such as filtering, sorting and paging.
 * When creating a Resource Collection, you need to specify which model it corresponds to, so that it can instantiate the
 * appropriate classes after loading a list of records. It is also necessary to know the matching resource model to be able
 * to access the database. A Resource Collection is necessary to create a set of model instances and operate on them.
 * Collections are very close to the database layer.
 */
class Collection extends KlaviyoCollection
{
    /**
     * Define model & resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Klaviyo\Reclaim\Model\Products',
            'Klaviyo\Reclaim\Model\ResourceModel\Products'
        );
    }
}
