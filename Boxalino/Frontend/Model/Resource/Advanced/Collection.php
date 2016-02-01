<?php
/**
 * Created by: Szymon Nosal <szymon.nosal@boxalino.com>
 * Created at: 06.06.14 11:36
 */

/**
 * Collection Advanced
 *
 * @category    Boxalino
 * @package     Boxalino_Frontend
 * @author      Szymon Nosal <szymon.nosal@boxalino.com>
 */
class Boxalino_Frontend_Model_Resource_Advanced_Collection extends Boxalino_Frontend_Model_Resource_Product_Collection
{
    /**
     * Add products id to search
     *
     * @param array $ids
     * @return Mage_CatalogSearch_Model_Resource_Advanced_Collection
     */
    public function addIdFromBoxalino($ids)
    {
        $this->addFieldToFilter('entity_id', array('in' => $ids));
        return $this;
    }
}