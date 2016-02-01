<?php
/**
 * Created by: Simon Rupf <simon.rupf@boxalino.com>
 * Created at: 15.04.2015 14:47
 */

require_once 'Mage/Catalog/Model/Category.php';

/**
 * Catalog category
 *
 * @category    Boxalino
 * @package     Boxalino_Frontend
 * @author      Simon Rupf <simon.rupf@boxalino.com>
 */
class Boxalino_Frontend_Model_Category extends Mage_Catalog_Model_Category
{
    /**
     * Retrieve Available Product Listing  Sort By
     * code as key, value - name
     *
     * @return array
     */
    public function getAvailableSortByOptions() {
        $availableSortBy = array();
        $defaultSortBy   = Mage::getSingleton('catalog/config')
            ->getAttributeUsedForSortByArray();
        if ($this->getAvailableSortBy()) {
            foreach ($this->getAvailableSortBy() as $sortBy) {
                if (isset($defaultSortBy[$sortBy])) {
                    $availableSortBy[$sortBy] = $defaultSortBy[$sortBy];
                }
            }
        }

        if (!$availableSortBy) {
            $availableSortBy = $defaultSortBy;
        }

        // force ordering of results by position returned by p13n
        $availableSortBy = array_merge(array('relevance'=>'Relevance'), $availableSortBy);
        unset($availableSortBy['position']);

        return $availableSortBy;
    }
}