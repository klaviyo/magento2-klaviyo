<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\Events;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init( 'Klaviyo\Reclaim\Model\Events', 'Klaviyo\Reclaim\Model\ResourceModel\Events' );
    }

    public function getEventsToUpdate()
    {
        $eventsCollection = $this->addFieldToSelect( ['id','event','payload','user_properties'] )
            ->addFieldToFilter( 'status','New' )
            ->addOrder( 'created_at', self::SORT_ORDER_ASC )
            ->setPageSize( 500 );

        return $eventsCollection;
    }

}
