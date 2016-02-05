<?php
/**
 * Collection Fulltext
 *
 * @category    Boxalino
 * @package     Boxalino_Frontend
 * @author      Simon Rupf <simon.rupf@boxalino.com>
 */
abstract class  Boxalino_Frontend_Model_Resource_Fulltext_Collection implements \Magento\Framework\ObjectManager\RelationsInterface
{
    /**
     * Retrieve collection all items count
     *
     * @return int
     */
    public function getSize()
    {
        return Mage::helper('Boxalino_Frontend')->getSearchAdapter()->getTotalHitCount();
    }


    /**
     * Render sql select limit
     *
     * @return  Varien_Data_Collection_Db
     */
    protected function _renderLimit()
    {
        // ignore limit
        $this->_select->limit();
        return $this;
    }
}