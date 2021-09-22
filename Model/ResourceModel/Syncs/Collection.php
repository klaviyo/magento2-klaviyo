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

    public function getIdsToDelete()
    {
        $now = new \DateTime('now');
        $date = $now->sub( new \DateInterval('P2D') )->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();

        $idsToDelete = $this->getConnection()->fetchAll( "select id from (
                                                            select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                                                            from $tableName
                                                            where status = '".self::SYNCED."'
                                                            having row_age_in_days > 2
                                                          ) as age_of_rows;"
        );

        return $idsToDelete;

    }
}
