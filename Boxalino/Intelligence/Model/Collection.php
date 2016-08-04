<?php
namespace Boxalino\Intelligence\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoCollection;

/**
 * Class Collection
 * @package Boxalino\Intelligence\Model
 */
class Collection extends MagentoCollection{
    
    /**
     * @var int
     */
    protected $bxCurPage = 0;
    
    /**
     * @var int
     */
    protected $bxLastPage = 0;

    /**
     * @var int
     */
    protected $bxTotal = 0;

    /**
     * @param $bxCurPage
     */
    public function setCurBxPage($bxCurPage) {
        
        $this->bxCurPage = $bxCurPage;
    }

    /**
     * @param $bxLastPage
     */
    public function setLastBxPage($bxLastPage) {
        
        $this->bxLastPage = $bxLastPage;
    }

    /**
     * @param $bxTotal
     */
    public function setBxTotal($bxTotal) {
        
        $this->bxTotal = $bxTotal;
    }

    /**
     * @return int
     */
    public function getSize() {
        
        return $this->bxTotal;
    }

    /**
     * @param int $displacement
     * @return int
     */
    public function getCurPage($displacement = 0) {
        
        return $this->bxCurPage + $displacement;
    }

    /**
     * @return int
     */
    public function getLastPageNumber() {
        
        return $this->bxLastPage;
    }
}