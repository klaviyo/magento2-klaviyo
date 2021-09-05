<?php

namespace Klaviyo\Reclaim\Model\ResourceModel\KlProduct;

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
        'Klaviyo\Reclaim\Model\KlProduct',
        'Klaviyo\Reclaim\Model\ResourceModel\KlProduct'
    );
    }

    public function getKlProductsToQueueForSync()
    {
        $productsCollection = $this->addFieldToSelect( ['id','payload','status','topic'] )
            ->addFieldToFilter( 'status','NEW' )
            ->addOrder( 'id', self::SORT_ORDER_ASC )
            ->setPageSize( 5 );

        return $productsCollection;
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
