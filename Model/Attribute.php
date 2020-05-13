<?php
namespace Boxalino\Intelligence\Model;

use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

/**
 * Class Attribute
 * @package Boxalino\Intelligence\Model
 */
class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute
{

    /**
     * @var \com\boxalino\bxclient\v1\BxFacets
     */
    protected $bxFacets = null;

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxDataHelper;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $categoryHelper;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_layer;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_config;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var string
     */
    protected $_locale;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Attribute constructor.
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Catalog\Helper\Category $categoryHelper
     * @param \Magento\Eav\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Locale\Resolver $locale
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
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
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data=[]
    ){
        $this->objectManager = $objectManager;
        $this->_locale = substr($locale->getLocale(), 0, 2);
        $this->_logger = $logger;
        $this->_config = $config;
        $this->_layer = $layer;
        $this->categoryHelper = $categoryHelper;
        $this->categoryFactory = $categoryFactory;
        $this->bxDataHelper = $bxDataHelper;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
    }

    /**
     * @param \com\boxalino\bxclient\v1\BxFacets $bxFacets
     */
    public function setFacets(\com\boxalino\bxclient\v1\BxFacets $bxFacets)
    {
        $this->bxFacets = $bxFacets;
    }

    /**
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    public function getBxFacets()
    {
        if($this->bxFacets == null) {
            $this->p13nHelper->notifyWarning(["message" => "Attribute requested getBxFacets before bxFacets have been set!", "stacktrace" => $this->bxDataHelper->notificationTrace()]);
            $this->bxFacets = $this->p13nHelper->getFacets();
        }
        return $this->bxFacets;
    }

    /**
     * Return the configured Magento2 label if there is none configured in Boxalino Intelligence
     *
     * @return mixed
     */
    public function getName()
    {
        $bxConfiguredName = $this->getBxFacets()->getFacetLabel($this->fieldName, $this->_locale);
        if(substr($bxConfiguredName, 0, strlen($this->bxDataHelper->getProductAttributePrefix())) == $this->bxDataHelper->getProductAttributePrefix())
        {
            $label = $this->_getAttributeModel()->getStoreLabel();
            if(!empty($label))
            {
                return $label;
            }
        }

        return $bxConfiguredName;
    }

    /**
     * @return $this|\Magento\Catalog\Model\Layer\Filter\AbstractFilter
     */
    public function _initItems()
    {
        try{
            if($this->bxDataHelper->isEnabledOnLayer($this->_layer) && $this->bxDataHelper->isPluginEnabled())
            {
                $data = $this->_getItemsData();
                $items = [];
                foreach ($data as $itemData) {
                    if($this->fieldName == 'discountedPrice' && substr($itemData['label'], -3) == '- 0')
                    {
                        $values = explode(' - ', $itemData['label']);
                        $values[1] = '*';
                        $itemData['label'] = implode(' - ', $values);
                    }
                    $selected = isset($itemData['selected']) ? $itemData['selected'] : null;
                    $type = isset($itemData['type']) ? $itemData['type'] : null;
                    $hidden = isset($itemData['hidden']) ? $itemData['hidden'] : null;
                    $items[$itemData['label']] = $this->_createItem($itemData['label'], $itemData['value'], $itemData['count'], $selected, $type, $hidden);
                }
                $this->_items = $items;
                return $this;
            }
            return parent::_initItems();
        } catch (\Exception $e){
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
    public function _createItem($label, $value, $count = 0, $selected = null, $type = null, $hidden = null)
    {
        if($this->bxDataHelper->isEnabledOnLayer($this->_layer) && $this->bxDataHelper->isPluginEnabled())
        {
            $filter = $this->objectManager->create("Boxalino\Intelligence\Model\LayerFilterItem");
            $filter
                ->setFilter($this)
                ->setLabel($label)
                ->setValue($value)
                ->setCount($count)
                ->setSelected($selected)
                ->setType($type)
                ->setHidden($hidden);
            return $filter;
        }
        return parent::_createItem($label, $value, $count);
    }

    /**
     * @return array
     */
    protected function _getItemsData()
    {
        $bxFacets = $this->getBxFacets();
        $isSystemFilter = $this->isSystemFilter();
        $facetOptions = $this->bxDataHelper->getFacetOptions();
        $isMultiValued = isset($facetOptions[$this->fieldName]) ? true : false;
        if($isSystemFilter) {
            $this->_requestVar = str_replace('bx_products_', '', $bxFacets->getFacetParameterName($this->fieldName));
            $this->setAttributeModel($this->_getAttributeModel());
        } else {
            $this->_requestVar = $bxFacets->getFacetParameterName($this->fieldName);
        }

        $this->_requestVar = str_replace('bx_products_', '', $bxFacets->getFacetParameterName($this->fieldName));
        $order = $bxFacets->getFacetExtraInfo($this->fieldName, 'valueorderEnums');

        if ($this->fieldName == $bxFacets->getCategoryFieldName()) {
            $count = 1;
            $parentCategories = $bxFacets->getParentCategories();
            $parentCount = count($parentCategories);
            $value = false;
            foreach ($parentCategories as $key => $parentCategory) {
                if ($count == 1) {
                    $count++;
                    $homeLabel = __("All Categories");
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($homeLabel),
                        $this->_storeManager->getStore()->getRootCategoryId(),
                        $bxFacets->getParentCategoriesHitCount($key),
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
                    $value ? null : $key,
                    $bxFacets->getParentCategoriesHitCount($key),
                    $value,
                    'parent',
                    false
                );
            }
            $facetValues = null;
            if($order == 'custom'){
                $facetLabels = $bxFacets->getCategoriesKeyLabels();
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
                $facetValues = $bxFacets->getFacetValues($this->fieldName);
            }

            foreach ($facetValues as $facetValue) {
                $id =  $bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue);
                if($this->categoryHelper->canShow($this->categoryFactory->create()->load($id))){
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        $id,
                        $bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        false,
                        $value ? 'children' : 'home',
                        $bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                    );
                }
            }
        } else {
            $attributeSource = $this->_getSource();
            if ($order == 'custom') {
                $values = $attributeSource->getAllOptions();
                $responseValues = $this->bxDataHelper->useValuesAsKeys($bxFacets->getFacetValues($this->fieldName));
                $selectedValues = $this->bxDataHelper->useValuesAsKeys($bxFacets->getSelectedValues($this->fieldName));
                foreach($values as $value){
                    $label = is_array($value) ? $value['label'] : $value;
                    if(isset($responseValues[$label])){
                        $facetValue = $responseValues[$label];
                        $selected = isset($selectedValues[$facetValue]) ? true : false;
                        $paramValue = $this->getParamValue($isSystemFilter, $bxFacets, $this->fieldName, $facetValue, $attributeSource, $selectedValues, $selected, $isMultiValued);
                        $this->itemDataBuilder->addItemData(
                            $this->tagFilter->filter($bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                            $paramValue,
                            $bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                            $selected,
                            'flat',
                            $bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                        );
                    }
                }
            } else {
                $selectedValues = $this->bxDataHelper->useValuesAsKeys($bxFacets->getSelectedValues($this->fieldName));
                $responseValues = $bxFacets->getFacetValues($this->fieldName);
                foreach ($responseValues as $facetValue) {
                    $selected = $bxFacets->isFacetValueSelected($this->fieldName, $facetValue);
                    $paramValue = $this->getParamValue($isSystemFilter, $bxFacets, $this->fieldName, $facetValue, $attributeSource, $selectedValues, $selected, $isMultiValued);
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->getFacetValueLabel($this->fieldName, $facetValue)),
                        $paramValue,
                        $bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $selected,
                        'flat',
                        $bxFacets->isFacetValueHidden($this->fieldName, $facetValue)
                    );
                }
            }
        }
        return $this->itemDataBuilder->build();
    }

    /**
     * @param $field
     * @param $value
     * @return mixed
     */
    public function getFacetValueLabel($field, $value)
    {
        if($this->_getSource() instanceof Boolean)
        {
            return $this->_getSource()->getOptionText($value);
        }

        return $this->getBxFacets()->getFacetValueLabel($field, $value);
    }

    /**
     * @param $isSystemFilter
     * @param $bxFacets
     * @param $fieldName
     * @param $facetValue
     * @param $attributeSource
     * @param $selectedValues
     * @param $selected
     * @param bool $setCurrentSelection
     * @return string|null
     */
    public function getParamValue($isSystemFilter, $bxFacets, $fieldName, $facetValue, $attributeSource,
                                  $selectedValues, $selected, $setCurrentSelection=false
    ){
        $paramValue = ($selected ? null : ($isSystemFilter ? $attributeSource->getOptionId($facetValue)
            : $bxFacets->getFacetValueParameterValue($fieldName, $facetValue)));

        if($selected && isset($selectedValues[$facetValue]))unset($selectedValues[$facetValue]);
        if($setCurrentSelection && sizeof($selectedValues)>0) {
            $separator = $this->bxDataHelper->getSeparator();
            if(!is_null($paramValue)) $paramValue .= $separator;
            if(!$isSystemFilter) {
                $paramValue .= implode($separator, $selectedValues);
                return $paramValue;
            }else {

                $changedSelection = [];
                foreach($selectedValues as $selected) {
                    $changedSelection[] = $attributeSource->getOptionId($selected);
                }
                $paramValue .= implode($separator, $changedSelection);
            }
        }
        return $paramValue;
    }

    /**
     * @param $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getAttributeModel()
    {
        return $this->_config->getAttribute('catalog_product', substr($this->fieldName, 9));
    }

    /**
     * @return mixed
     */
    public function _getSource()
    {
        return $this->_getAttributeModel()->getSource();
    }


    /**
     * @return bool
     */
    public function isSystemFilter()
    {
        $source = $this->_getSource();
        return sizeof($source->getAllOptions()) > 1;
    }

}
