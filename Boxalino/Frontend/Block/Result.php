<?php

class Boxalino_CemSearch_Block_Result extends Mage_CatalogSearch_Block_Result
{
    /**
     * Retrieve loaded category collection
     *
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Collection
     */
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $this->_productCollection = $this->getListBlock()->getLoadedProductCollection();
        }
        // reset limits set by the toolbar
        $this->_productCollection->clear();
        $this->_productCollection->getSelect()->limit();

        return $this->_productCollection;
    }

    /**
     * Retrieve search result count
     *
     * @return string
     */
    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = Mage::helper('Boxalino_CemSearch')->getSearchAdapter()->getTotalHitCount();
            $this->_getQuery()->setNumResults($size);
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }
}