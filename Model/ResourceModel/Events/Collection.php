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

    public function getIdsToDelete()
    {
        $now = new \DateTime('now');
        $date = $now->sub( new \DateInterval('P2D') )->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();

        $idsToDelete = $this->getConnection()->fetchAll( "select id from (
                                                            select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                                                            from $tableName
                                                            where status = '".self::MOVED."'
                                                            having row_age_in_days > 2
                                                          ) as age_of_rows;"
        );

        return $idsToDelete;
    }
}
