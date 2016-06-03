<?php

namespace Boxalino\Intelligence\Model;

class Attribute extends \Magento\Catalog\Model\Layer\Filter\Attribute {

    private $bxFacets = null;
    private $fieldName = array();
    private $bxDataHelper;
    private $categoryHelper;
    private $categoryTreeNodes;
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\AttributeFactory $filterAttributeFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        \Magento\Catalog\Helper\Category $categoryHelper,
        array $data=[])
    {
        $this->bxDataHelper = $bxDataHelper;
        $this->categoryHelper = $categoryHelper;
        parent::__construct($filterItemFactory, $storeManager, $layer, $itemDataBuilder, $filterAttributeFactory, $string, $tagFilter, $data);
    }

    public function setFacets($bxFacets) {
        $this->bxFacets = $bxFacets;
    }

    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    public function getName()
    {
        return $this->bxFacets->getFacetLabel($this->fieldName);
    }

    public function getFieldName(){
        return $this->fieldName;
    }

    private function getCategoryTreeNodes(){
        return $this->categoryTreeNodes;
    }

    private function setCategoryTreeNodes($_categoryTreeNodes){
        $this->categoryTreeNodes = $_categoryTreeNodes;
    }
    public function _initItems()
    {
        if($this->bxDataHelper->isFilterLayoutEnabled()){
            $data = $this->_getItemsData();
            $items = [];
            foreach ($data as $itemData) {
                $selected = isset($itemData['selected']) ?$itemData['selected'] : null;
                $type = isset($itemData['type']) ?$itemData['type'] : null;
                $items[] = $this->_createItem($itemData['label'], $itemData['value'], $itemData['count'], $selected, $type);
            }
            $this->_items = $items;
            return $this;
        }
        return parent::_initItems();
    }

    public function _createItem($label, $value, $count = 0, $selected = null, $type = null)
    {
        if($this->bxDataHelper->isFilterLayoutEnabled()) {
            return $this->_filterItemFactory->create()
                ->setFilter($this)
                ->setLabel($label)
                ->setValue($value)
                ->setCount($count)
                ->setSelected($selected)
                ->setType($type);
        }
        return parent::_createItem($label, $value, $count);
    }

    protected function _getItemsData()
    {
        $this->_requestVar = $this->bxFacets->getFacetParameterName($this->fieldName);
        if (!$this->bxDataHelper->isHierarchical($this->fieldName)) {
            foreach ($this->bxFacets->getFacetValues($this->fieldName) as $facetValue) {
                if ($this->bxFacets->getSelectedValues($this->fieldName) && $this->bxFacets->getSelectedValues($this->fieldName)[0] == $facetValue) {
                    $value = $this->bxFacets->getSelectedValues($this->fieldName)[0] == $facetValue ? true : false;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        0,
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $value,
                        'flat'
                    );
                } else {
                    $value = false;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                        $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue),
                        $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                        $value,
                        'flat'
                    );
                }
            }
        } else {
            $count = 1;
            $parentCategories = $this->bxFacets->getParentCategories();
            $parentCount = count($parentCategories);
            $value = false;
            foreach ($parentCategories as $key => $parentCategory) {
                if ($count == 1) {
                    $count++;
                    $this->itemDataBuilder->addItemData(
                        $this->tagFilter->filter("Home"),
                        2,
                        $this->bxFacets->getParentCategoriesHitCount($key),
                        $value,
                        'home parent'
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
                    'parent'
                );
            }
            
            if($this->bxDataHelper->getCategoriesSortOrder() == 2){
				
				$categoryTree = array();
				if(sizeof($parentCategories) < 2) {
					$categoryTree = $this->categoryHelper->getStoreCategories(true, false, true);
				} else {
					$keys = array_keys($parentCategories);
					$lastKey = $keys[sizeof($keys)-1];
					$categoryTree = $this->categoryHelper->searchById($key)->getChildren();
				}
                $_categoryTreeNodes = array();
                $treeIterator = $categoryTree->getIterator();
                while($treeIterator->valid()){
                    if($treeIterator->current()->getIsActive()){
                        $_categoryTreeNodes[] = $treeIterator->current()->getName();
                    }
                    $treeIterator->next();
                }
                $this->setCategoryTreeNodes($_categoryTreeNodes);
                $facetValues = $this->sortCategories($this->bxFacets->getCategoriesKeyLabels());
            } else{
                $facetValues = $this->bxFacets->getCategories();
            }
            foreach ($facetValues as $facetValue) {
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($this->bxFacets->getFacetValueLabel($this->fieldName, $facetValue)),
                    $this->bxFacets->getFacetValueParameterValue($this->fieldName, $facetValue),
                    $this->bxFacets->getFacetValueCount($this->fieldName, $facetValue),
                    false,
                    $value ? 'children' : 'home children'
                );
            }
        }
        return $this->itemDataBuilder->build();
    }

    private function sortCategories($categories){
        $sortedCategories = array();
        $categoryTreeNodes = $this->getCategoryTreeNodes();
		foreach($categoryTreeNodes as $node){
			if(isset($categories[$node])) {
				$sortedCategories[] = $categories[$node];
			}
		}		
        return $sortedCategories;
    }
}
