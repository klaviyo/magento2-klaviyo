<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\Products;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
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

    public function getProductsToUpdate()
    {
        $productsCollection = $this->addFieldToSelect( ['id','payload','status','topic', 'klaviyo_id'] )
            ->addFieldToFilter( 'status','NEW' )
            ->addOrder( 'id', self::SORT_ORDER_ASC )
            ->setPageSize( 5 );

        return $productsCollection;
    }

    public function getIdsToDelete()
    {
        $now = new \DateTime('now');
        $date = $now->sub( new \DateInterval('P2D') )->format('Y-m-d H:i:s');

        $tableName = $this->getMainTable();

        $rowsToDelete = $this->getConnection()
            ->fetchAll( "select id from (
                         select id, timestampdiff(day, created_at, \"$date\") as row_age_in_days
                         from $tableName
                         where status = 'MOVED'
                         having row_age_in_days > 2
                      ) as age_of_rows;"
            );
        return $rowsToDelete;
    }
}
