<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\KlSync;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
    $this->_init(
        'Klaviyo\Reclaim\Model\KlSync',
        'Klaviyo\Reclaim\Model\ResourceModel\KlSync'
    );

    }

    public function getRowsForSync()
    {
      $syncCollection = $this->addFieldToSelect( ['id','payload','status','topic', 'klaviyo_id'] )
          ->addFieldToFilter( 'status','New' )
          ->addOrder( 'id', self::SORT_ORDER_ASC )
          ->setPageSize( 100 );

      return $syncCollection;
    }

    public function getRowsForRetrySync()
    {
      $syncCollection = $this->addFieldToSelect( ['id','payload','status','topic', 'klaviyo_id'] )
          ->addFieldToFilter( 'status','Retry' )
          ->addOrder( 'id', self::SORT_ORDER_ASC )
          ->setPageSize( 100 );

      return $syncCollection;
    }

    public function getIdsToDelete()
    {
      // $now = new \DateTime('now');
      // $date = $now->sub( new \DateInterval('P2D') )->format('Y-m-d H:i:s');
      //
      // $idsToDelete = $this->addFieldToSelect( 'id' )
      //     ->addFieldToFilter('status', 'Moved')
      //     ->addFieldToFilter("TIMESTAMPDIFF(day, created_at, $date)", array( 'gt' => 2 ) );

      return "value returned from getIdsToDelete";
    }
}
