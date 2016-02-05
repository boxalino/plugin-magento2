<?php
namespace Boxalino\Frontend\Model;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogSearch\Model\ResourceModel\Advanced\Collection as ProductCollection;
use Magento\CatalogSearch\Model\ResourceModel\AdvancedFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Magento\Framework\Model\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogSearch\Helper\Data;
class Advanced extends \Magento\CatalogSearch\Model\Advanced
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
    protected $scopeConfig;
    protected $helperData;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    public function __construct(
        Context $context,
        Registry $registry,
        AttributeCollectionFactory $attributeCollectionFactory,
        Visibility $catalogProductVisibility,
        Config $catalogConfig,
        CurrencyFactory $currencyFactory,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        AdvancedFactory $advancedFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helperData,
        array $data = []
    )
    {
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $registry, $attributeCollectionFactory, $catalogProductVisibility, $catalogConfig, $currencyFactory, $productFactory, $storeManager, $productCollectionFactory, $advancedFactory, $data);
    }
//    public function __construct(
//        Context $context,
//        Registry $registry,
//        AttributeCollectionFactory $attributeCollectionFactory,
//        Visibility $catalogProductVisibility,
//        Config $catalogConfig,
//        CurrencyFactory $currencyFactory,
//        ProductFactory $productFactory,
//        StoreManagerInterface $storeManager,
//        ProductCollectionFactory $productCollectionFactory,
//        AdvancedFactory $advancedFactory,
//        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
//        array $data
//    )
//    {
//        $this->scopeConfig = $scopeConfig;
//        parent::__construct($context, $registry, $attributeCollectionFactory,
//            $catalogProductVisibility, $catalogConfig, $currencyFactory,
//            $productFactory, $storeManager, $productCollectionFactory, $advancedFactory, $data);
//    }


    public function addFilters($values, $ids = null)
    {
        if ($this->scopeConfig->getValue('Boxalino_General/general/enabled',$this->scopeStore) == 0) {
            return parent::addFilters($values);
        }

        $attributes = $this->getAttributes();
        exit;
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
                $rate = 1;
                $store = $this->_storeManager->getStore();
                $currency = $store->getCurrentCurrencyCode();
                if ($currency != $store->getBaseCurrencyCode()) {
                    $rate = $store->getBaseCurrency()->getRate($currency);
                }
                $value['from'] = (isset($value['from']) && is_numeric($value['from']))
                    ? (float)$value['from'] / $rate
                    : '';
                $value['to'] = (isset($value['to']) && is_numeric($value['to']))
                    ? (float)$value['to'] / $rate
                    : '';
                $this->_addSearchCriteria($attribute, $value);
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
            throw new LocalizedException(__('Please specify at least one search term.'));
        }

        return $this;
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
//    protected function _getResource()
//    {
//        $resourceName = $this->_engine->getResourceName();
//        if ($resourceName) {
//            $this->_resourceName = $resourceName;
//        }
//        return parent::_getResource();
//    }

    /**
     * Retrieve advanced search product collection
     *
     * @return Mage_CatalogSearch_Model_Resource_Advanced_Collection
     */
    public function getProductCollection()
    {
        return parent::getProductCollection();
    }

    /**
     * Prepare product collection
     *
     * @param Mage_CatalogSearch_Model_Resource_Advanced_Collection $collection
     * @return Mage_Catalog_Model_Layer
     */
    public function prepareProductCollection($collection)
    {
        return parent::prepareProductCollection($collection);
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


}