<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\Syncs;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
    $this->_init(
        'Klaviyo\Reclaim\Model\Syncs',
        'Klaviyo\Reclaim\Model\ResourceModel\Syncs'
    );

    }

    public function getRowsForSync()
    {
      $syncCollection = $this->addFieldToFilter( 'status','NEW' )
          ->addOrder( 'id', self::SORT_ORDER_ASC )
          ->setPageSize( 100 );

      return $syncCollection;
    }

    public function getRowsForRetrySync()
    {
      $syncCollection = $this->addFieldToFilter( 'status','RETRY' )
          ->addOrder( 'id', self::SORT_ORDER_ASC )
          ->setPageSize( 100 );

      return $syncCollection;
    }

}
