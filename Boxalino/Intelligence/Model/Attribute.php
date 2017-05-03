<?php

namespace Boxalino\Intelligence\Model;

/**
 * Class Attribute
 * @package Boxalino\Intelligence\Model
 */
class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute {

    /**
     * @var \com\boxalino\bxclient\v1\BxFacets
     */
    private $bxFacets = null;

    /**
     * @var array
     */
    private $fieldName = array();

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxDataHelper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    private $categoryHelper;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    private $_layer;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $_config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var
     */
    private $is_bx_attribute;

    /**
     * @var string
     */
    private $_locale;

    /**
     * Attribute constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Eav\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Magento\Eav\Model\Config $config,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Locale\Resolver $locale,
        array $data=[]
    )
    {
        $this->_locale = substr($locale->getLocale(), 0, 2);
        $this->_logger = $logger;
        $this->_config = $config;
        $this->_layer = $layer;
        $this->categoryHelper = $categoryHelper;
        $this->categoryFactory = $categoryFactory;
        $this->bxDataHelper = $bxDataHelper;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
    }

    /**
     * @param \com\boxalino\bxclient\v1\BxFacets $bxFacets
     */
    public function setFacets(\com\boxalino\bxclient\v1\BxFacets $bxFacets) {
        
        $this->bxFacets = $bxFacets;
    }

    /**
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    public function getBxFacets() {

        return $this->bxFacets;
    }
    
    /**
     * @param $fieldName
     */
    public function setFieldName($fieldName) {
        
        $this->fieldName = $fieldName;
    }

    /**
     * @return mixed
     */
    public function getName(){

        return $this->bxFacets->getFacetLabel($this->fieldName, $this->_locale);
    }

    /**
     * @return array
     */
    public function getFieldName(){
        
        return $this->fieldName;
    }

    /**
     * @return $this|\Magento\Catalog\Model\Layer\Filter\AbstractFilter
     */
    public function _initItems(){
        
        try{
            if($this->bxDataHelper->isEnabledOnLayer($this->_layer)){
                $data = $this->_getItemsData();
                $items = [];
                foreach ($data as $itemData) {
                    $selected = isset($itemData['selected']) ? $itemData['selected'] : null;
                    $type = isset($itemData['type']) ? $itemData['type'] : null;
                    $hidden = isset($itemData['hidden']) ? $itemData['hidden'] : null;
                    $items[] = $this->_createItem($itemData['label'], $itemData['value'], $itemData['count'], $selected, $type, $hidden);
                }
                $this->_items = $items;
                return $this;
            }
            return parent::_initItems();  
        }catch (\Exception $e){
            $this->bxDataHelper->setFallback(true);
            $this->_logger->critical($e);
            return parent::_initItems();
        }
    }

    /**
     * @param string $label
     * @param mixed $value
     * @param int $count
     * @param null $selected
     * @param null $type
     * @return \Magento\Catalog\Model\Layer\Filter\Item
     */
    public function _createItem($label, $value, $count = 0, $selected = null, $type = null, $hidden = null){

        if($this->bxDataHelper->isEnabledOnLayer($this->_layer)) {
            return $this->_filterItemFactory->create()
                ->setFilter($this)
                ->setLabel($label)
                ->setValue($value)
                ->setCount($count)
                ->setSelected($selected)
                ->setType($type)
                ->setHidden($hidden);
        }
        return parent::_createItem($label, $value, $count);
    }

    /**
     * @return array
     */
    protected function _getItemsData(){

        $this->_requestVar = str_replace('bx_products_', '', $this->bxFacets->getFacetParameterName($this->fieldName));
        $order = $this->bxDataHelper->getFieldSortOrder($this->fieldName);
        
        if ($this->fieldName == $this->bxFacets->getCategoryFieldName()) {
            $count = 1;
            $parentCategories = $this->bxFacets->getParentCategories();
            $parentCount = count($parentCategories);
            $value = false;
            foreach ($parentCategories as $key => $parentCategory) {
                if ($count == 1) {
                    $count++;
                    $homeLabel = __("All Categories");
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($homeLabel),
                        2,
                        $this->bxFacets->getParentCategoriesHitCount($key),
                        $value,
                        'home parent',
                        false
                    );
                    continue;
                }
                if ($parentCount == $count++) {
                    $value = true;
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($parentCategory),
                    $key,
                    $this->bxFacets->getParentCategoriesHitCount($key),
                    $value,
                    'parent',
                    false
                );
            }
            $facetValues = null;
            if($order == 2){
                $facetLabels = $this->bxFacets->getCategoriesKeyLabels();
                $childId = explode('/',end($facetLabels))[0];
                $childParentId = $this->categoryFactory->create()->load($childId)->getParentId();
                end($parentCategories);
                $parentId = key($parentCategories);
                $id = (($parentId == null) ? 2 : (($parentId == $childParentId) ? $parentId : $childParentId));

                $cat = $this->categoryFactory->create()->load($id);
                foreach($cat->getChildrenCategories() as $category){
                    if(isset($facetLabels[$category->getName()])) {
                        $facetValues[] = $facetLabels[$category->getName()];
                    }
                }
            }
            if($facetValues == null){
                $facetValues = $this->bxFacets->getFacetValues($this->fieldName);
            }

            foreach ($facetValues as $facetValue) {
                $id =  $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue);
                if($this->categoryHelper->canShow($this->categoryFactory->create()->load($id))){
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        $id,
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        false,
                        $value ? 'children' : 'home',
                        $this->bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                    );
                }
            }
        } else {

            $attributeModel = $this->_config->getAttribute('catalog_product', substr($this->fieldName,9))->getSource();
            if ($order == 2) {
                $responseValues = $this->bxDataHelper->useValuesAsKeys($this->bxFacets->getFacetValues($this->fieldName));
                $values = $attributeModel->getAllOptions();

                foreach($values as $value){
                    $label = is_array($value) ? $value['label'] : $value;
                    if(isset($responseValues[$label])){
                        $facetValue = $responseValues[$label];
                        $selected = $this->bxFacets->isFacetValueSelected($this->fieldName, $facetValue);
                        $paramValue = $this->is_bx_attribute ? $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue): $attributeModel->getOptionId($this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue));
                        $this->itemDataBuilder->addItemData(
                            $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                            $selected ? 0 : $paramValue,
                            $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                            $selected,
                            'flat',
                            $this->bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                        );
                    }
                }
            } else {
                foreach ($this->bxFacets->getFacetValues($this->fieldName) as $facetValue) {
                    $selected = $this->bxFacets->isFacetValueSelected($this->fieldName, $facetValue);
                    $paramValue = false ? $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue):
                        $attributeModel->getOptionId($this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue));

                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        $selected ? 0 : $paramValue,
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $selected,
                        'flat',
                        $this->bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                    );
                }
            }
           
        }
        return $this->itemDataBuilder->build();
    }
}
