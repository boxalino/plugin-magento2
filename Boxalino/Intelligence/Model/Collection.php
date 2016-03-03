<?php
namespace Boxalino\Intelligence\Model;

use Magento\Catalog\Model\ResourceModel\Product\Collection as MagentoCollection;
class Collection extends MagentoCollection
{
    protected $bxCurPage = 0;
    protected $bxLastPage = 0;
    protected $bxTotal = 0;

    public function setCurBxPage($bxCurPage) {
        $this->bxCurPage = $bxCurPage;
    }

    public function setLastBxPage($bxLastPage) {
        $this->bxLastPage = $bxLastPage;
    }

    public function setBxTotal($bxTotal) {
        $this->bxTotal = $bxTotal;
    }

    public function getSize() {
        return $this->bxTotal;
    }

    public function getCurPage($displacement = 0) {
        return $this->bxCurPage + $displacement;
    }

    public function getLastPageNumber() {
        return $this->bxLastPage;
    }
}