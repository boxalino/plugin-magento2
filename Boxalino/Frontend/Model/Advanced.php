<?php
/**
 * Created by: Szymon Nosal <szymon.nosal@boxalino.com>
 * Created at: 06.06.14 11:45
 */

require_once 'Mage/CatalogSearch/Model/Advanced.php';

/**
 *
 * @category    Mage
 * @package     Mage_CatalogSearch
 * @author      Szymon Nosal <szymon.nosal@boxalino.com>
 */
class Boxalino_Frontend_Model_Advanced extends Mage_CatalogSearch_Model_Advanced
{
    /**
     * User friendly search criteria list
     *
     * @var array
     */
    protected $_searchCriterias = array();

    /**
     * Current search engine
     *
     * @var object|Mage_CatalogSearch_Model_Resource_Fulltext_Engine
     */
    protected $_engine;

    /**
     * Found products collection
     *
     * @var Mage_CatalogSearch_Model_Resource_Advanced_Collection
     */
    protected $_productCollection;

    /**
     * Add advanced search filters to product collection
     *
     * @param   array $values
     * @return  Mage_CatalogSearch_Model_Advanced
     */
    public function addFilters($values, $ids = null)
    {

        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::addFilters($values, $ids);
        }

        $attributes = $this->getAttributes();
        $hasConditions = true;
        $allConditions = array();

        foreach ($attributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!isset($values[$attribute->getAttributeCode()])) {
                continue;
            }
            $value = $values[$attribute->getAttributeCode()];
            if (!is_array($value)) {
                $value = trim($value);
            }

            if ($attribute->getAttributeCode() == 'price') {
                $value['from'] = isset($value['from']) ? trim($value['from']) : '';
                $value['to'] = isset($value['to']) ? trim($value['to']) : '';
                if (is_numeric($value['from']) || is_numeric($value['to'])) {
                    if (!empty($value['currency'])) {
                        $rate = Mage::app()->getStore()->getBaseCurrency()->getRate($value['currency']);
                    } else {
                        $rate = 1;
                    }
                    $this->_addSearchCriteria($attribute, $value);
                }
            } else if ($attribute->isIndexable()) {
                if (!is_string($value) || strlen($value) != 0) {
                    $this->_addSearchCriteria($attribute, $value);
                }
            } else {
                $condition = $this->_prepareCondition($attribute, $value);
                if ($condition === false) {
                    continue;
                }

                $this->_addSearchCriteria($attribute, $value);
            }
        }
        //Add id from boxalino
        $this->getProductCollection()->addIdFromBoxalino($ids);
        if ($allConditions) {
            $this->getProductCollection()->addIdFromBoxalino($allConditions);
        } else if (!$hasConditions) {
            Mage::throwException(Mage::helper('catalogsearch')->__('Please specify at least one search term.'));
        }

        return $this;
    }

    /**
     * Retrieve array of attributes used in advanced search
     *
     * @return array
     */
    public function getAttributes()
    {
        /* @var $attributes Mage_Catalog_Model_Resource_Eav_Resource_Product_Attribute_Collection */
        $attributes = $this->getData('attributes');
        if (is_null($attributes)) {
            $product = Mage::getModel('catalog/product');
            $attributes = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addHasOptionsFilter()
                ->addDisplayInAdvancedSearchFilter()
                ->addStoreLabel(Mage::app()->getStore()->getId())
                ->setOrder('main_table.attribute_id', 'asc')
                ->load();
            foreach ($attributes as $attribute) {
                $attribute->setEntity($product->getResource());
            }
            $this->setData('attributes', $attributes);
        }
        return $attributes;
    }

    /**
     * Add data about search criteria to object state
     *
     * @param   Mage_Eav_Model_Entity_Attribute $attribute
     * @param   mixed $value
     * @return  Mage_CatalogSearch_Model_Advanced
     */
    protected function _addSearchCriteria($attribute, $value)
    {
        $name = $attribute->getStoreLabel();

        if (is_array($value)) {
            if (isset($value['from']) && isset($value['to'])) {
                if (!empty($value['from']) || !empty($value['to'])) {
                    if (isset($value['currency'])) {
                        $currencyModel = Mage::getModel('directory/currency')->load($value['currency']);
                        $from = $currencyModel->format($value['from'], array(), false);
                        $to = $currencyModel->format($value['to'], array(), false);
                    } else {
                        $currencyModel = null;
                    }

                    if (strlen($value['from']) > 0 && strlen($value['to']) > 0) {
                        // -
                        $value = sprintf('%s - %s',
                            ($currencyModel ? $from : $value['from']), ($currencyModel ? $to : $value['to']));
                    } elseif (strlen($value['from']) > 0) {
                        // and more
                        $value = Mage::helper('catalogsearch')->__('%s and greater', ($currencyModel ? $from : $value['from']));
                    } elseif (strlen($value['to']) > 0) {
                        // to
                        $value = Mage::helper('catalogsearch')->__('up to %s', ($currencyModel ? $to : $value['to']));
                    }
                } else {
                    return $this;
                }
            }
        }

        if (($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect')
            && is_array($value)
        ) {
            foreach ($value as $key => $val) {
                $value[$key] = $attribute->getSource()->getOptionText($val);

                if (is_array($value[$key])) {
                    $value[$key] = $value[$key]['label'];
                }
            }
            $value = implode(', ', $value);
        } else if ($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
            $value = $attribute->getSource()->getOptionText($value);
            if (is_array($value))
                $value = $value['label'];
        } else if ($attribute->getFrontendInput() == 'boolean') {
            $value = $value == 1
                ? Mage::helper('catalogsearch')->__('Yes')
                : Mage::helper('catalogsearch')->__('No');
        }

        $this->_searchCriterias[] = array('name' => $name, 'value' => $value);
        return $this;
    }

    /**
     * Prepare search condition for attribute
     *
     * @deprecated after 1.4.1.0 - use Mage_CatalogSearch_Model_Resource_Advanced->_prepareCondition()
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param string|array $value
     * @return mixed
     */
    protected function _prepareCondition($attribute, $value)
    {
        return $this->_getResource()->prepareCondition($attribute, $value, $this->getProductCollection());
    }

    /**
     * Retrieve resource instance wrapper
     *
     * @return Mage_CatalogSearch_Model_Resource_Advanced
     */
    protected function _getResource()
    {
        $resourceName = $this->_engine->getResourceName();
        if ($resourceName) {
            $this->_resourceName = $resourceName;
        }
        return parent::_getResource();
    }

    /**
     * Retrieve advanced search product collection
     *
     * @return Mage_CatalogSearch_Model_Resource_Advanced_Collection
     */
    public function getProductCollection()
    {

        if (is_null($this->_productCollection)) {
            $collection = $this->_engine->getAdvancedResultCollection();
            $this->prepareProductCollection($collection);
            if (!$collection) {
                return $collection;
            }
            $this->_productCollection = $collection;
        }

        return $this->_productCollection;
    }

    /**
     * Prepare product collection
     *
     * @param Mage_CatalogSearch_Model_Resource_Advanced_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->setStore(Mage::app()->getStore())
            ->addMinimalPrice()
            ->addTaxPercents()
            ->addStoreFilter();

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($collection);

        return $this;
    }

    /**
     * Returns prepared search criterias in text
     *
     * @return array
     */
    public function getSearchCriterias()
    {
        return $this->_searchCriterias;
    }

    /**
     * Initialize resource model
     *
     */
    protected function _construct()
    {
        $this->_getEngine();
        $this->_init('catalogsearch/advanced');
    }

    protected function _getEngine()
    {
        if ($this->_engine == null) {
            $this->_engine = Mage::helper('catalogsearch')->getEngine();
        }

        return $this->_engine;
    }
}