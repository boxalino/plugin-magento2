<?php

namespace com\boxalino\bxclient\v1;

class BxFacets
{
	public $facets = array();
	protected $facetResponse = null;
	
	protected $parameterPrefix = '';
	
	protected $priceFieldName = 'discountedPrice';
	
	public function setFacetResponse($facetResponse) {
		$this->facetResponse = $facetResponse;
	}
	
	public function getCategoryFieldName() {
		return "categories";
	}
	
	private $filters = array();
	
	public function getFilters() {
		return $this->filters;
	}
	
	public function addCategoryFacet($selectedValue=null, $order=2) {
		if($selectedValue) {
			$this->addFacet('category_id', $selectedValue, 'hierarchical', '1');
		}
		$this->addFacet($this->getCategoryFieldName(), null, 'hierarchical', $order);
	}
	
	public function addPriceRangeFacet($selectedValue=null, $order=2, $label='Price', $fieldName = 'discountedPrice') {
		$this->priceFieldName = $fieldName;
		$this->addRangedFacet($fieldName, $selectedValue, $label, $order, true);
	}
	
	public function addRangedFacet($fieldName, $selectedValue=null, $label=null, $order=2, $boundsOnly=false) {
		$this->addFacet($fieldName, $selectedValue, 'ranged', $label, $order, $boundsOnly);
	}

	public function addFacet($fieldName, $selectedValue=null, $type='string', $label=null, $order=2, $boundsOnly=false) {
		$selectedValues = array();
		if($selectedValue) {
			$selectedValues[] = $selectedValue;
		}
		$this->facets[$fieldName] = array('label'=>$label, 'type'=>$type, 'order'=>$order, 'selectedValues'=>$selectedValues, 'boundsOnly'=>$boundsOnly);
	}
	
	public function setParameterPrefix($parameterPrefix) {
		$this->parameterPrefix = $parameterPrefix;
	}
	
	protected function isCategories($fieldName) {
		return strpos($fieldName, 'categories') !== false ;
	}

    public function getFacetParameterName($fieldName) {
		$parameterName = $fieldName;
		if($this->isCategories($fieldName)) {
			$parameterName = 'category_id';
		}
        return $this->parameterPrefix . $parameterName;
    }

    public function getFieldNames() {
        return array_keys($this->facets);
    }

    protected function getFacetResponse($fieldName) {
        foreach($this->facetResponse as $facetResponse) {
            if($facetResponse->fieldName == $fieldName) {
                return $facetResponse;
            }
        }
        throw new \Exception("trying to get facet response on unexisting fieldname " . $fieldName);
    }
	
	protected function getFacetType($fieldName) {
		$type = 'string';
		if(isset($this->facets[$fieldName])) {
			$type = $this->facets[$fieldName]['type'];
		}
		return $type;
	}
	
	protected function buildTree($response, $parents = array(), $parentLevel = 0) {
		if(sizeof($parents)==0) {
			foreach($response as $node) {
				if(sizeof($node->hierarchy) == 1) {
					$parents = $node->hierarchy;
				}
			}
		}
		$children = array();
		foreach($response as $node) {
			if(sizeof($node->hierarchy) == $parentLevel + 2) {
				$allTrue = true;
				foreach($parents as $k => $v) {
					if(!isset($node->hierarchy[$k]) || $node->hierarchy[$k] != $v) {
						$allTrue = false;
					}
				}
				if($allTrue) {
					$children[] = $this->buildTree($response, $node->hierarchy, $parentLevel+1);
				}
			}
		}
		foreach($response as $node) {
			if(sizeof($node->hierarchy) == $parentLevel + 1) {
				$allTrue = true;
				foreach($node->hierarchy as $k => $v) {
					if($parents[$k] != $v) {
						$allTrue = false;
					}
				}
				if($allTrue) {
					return array('node'=>$node, 'children'=>$children);
				}
			}
		}
		return null;
	}
	
	protected function getFirstNodeWithSeveralChildren($tree) {
		if(sizeof($tree['children']) == 0) {
			return null;
		}
		if(sizeof($tree['children']) > 1) {
			return $tree;
		}
		return $this->getFirstNodeWithSeveralChildren($tree['children'][0]);
	}
	
	public function getSelectedTreeNode($tree) {
		if(!isset($this->facets['category_id'])){
			return $tree;
		}
		if(!$tree['node']){
			return null;
		}
		$parts = explode('/', $tree['node']->stringValue);
		if($parts[0] == $this->facets['category_id']['selectedValues'][0]) {
			return $tree;
		}
		foreach($tree['children'] as $node) {
			$result = $this->getSelectedTreeNode($node);
			if($result != null) {
				return $result;
			}
		}
		return null;
	}
	
	protected function getFacetKeysValues($fieldName) {
		if($fieldName == "") {
			return array();
		}
        $facetValues = array();
        $facetResponse = $this->getFacetResponse($fieldName);
		$type = $this->getFacetType($fieldName);
		switch($type) {
		case 'hierarchical':
			$tree = $this->buildTree($facetResponse->values);
			$tree = $this->getSelectedTreeNode($tree);
			$node = $this->getFirstNodeWithSeveralChildren($tree);
			if($node) {
				foreach($node['children'] as $node) {
					$facetValues[$node['node']->stringValue] = $node['node'];
				}
			}
			break;
		case 'ranged':
			foreach($facetResponse->values as $facetValue) {
				$facetValues[$facetValue->rangeFromInclusive . '-' . $facetValue->rangeToExclusive] = $facetValue;
			}
			break;
		default:
			foreach($facetResponse->values as $facetValue) {
				$facetValues[$facetValue->stringValue] = $facetValue;
			}
			break;
		}
        return $facetValues;
	}
	
	public function getSelectedValues($fieldName) {
		$selectedValues = array();
        foreach($this->getFacetValues($fieldName) as $key) {
			if($this->isFacetValueSelected($fieldName, $key)) {
				$selectedValues[] = $key;
			}
		}
		return $selectedValues;
	}
	
	protected function getFacetByFieldName($fieldName) {
		foreach($this->facets as $fn => $facet) {
			if($fieldName == $fn) {
				return $facet;
			}
		}
		return null;
	}
	
	public function isSelected($fieldName, $ignoreCategories=false) {
		if($fieldName == "") {
			return false;
		}
		if($this->isCategories($fieldName)) {
			if($ignoreCategories) {
				return false;
			}
		}
		if(sizeof($this->getSelectedValues($fieldName)) > 0) {
			return true;
		}
		$facet = $this->getFacetByFieldName($fieldName);
		if($facet != null) {
			if($facet['type'] == 'hierarchical') {
				$facetResponse = $this->getFacetResponse($fieldName);
				$tree = $this->buildTree($facetResponse->values);
				$tree = $this->getSelectedTreeNode($tree);
				return $tree && sizeof($tree['node']->hierarchy)>1;
			}
			return isset($this->facets[$fieldName]['selectedValues']) && sizeof($this->facets[$fieldName]['selectedValues']) > 0;
		}
		return false;
	}
	
	public function getTreeParent($tree, $treeEnd) {
		foreach($tree['children'] as $child) {
			if($child['node']->stringValue == $treeEnd['node']->stringValue) {
				return $tree;
			}
			$parent = $this->getTreeParent($child, $treeEnd);
			if($parent) {
				return $parent;
			}
		}
		return null;
	}
	
	public function getParentCategories() {
		$fieldName = 'categories';
		$facetResponse = $this->getFacetResponse($fieldName);
		$tree = $this->buildTree($facetResponse->values);
		$treeEnd = $this->getSelectedTreeNode($tree);
		if($treeEnd == null) {
			return array();
		}
		if($treeEnd['node']->stringValue == $tree['node']->stringValue) {
			return array();
		}
		$parents = array();
		$parent = $treeEnd;
		while($parent) {
			$parts = explode('/', $parent['node']->stringValue);
			$parents[] = array($parts[0], $parts[sizeof($parts)-1]);
			$parent = $this->getTreeParent($tree, $parent);
		}
		krsort($parents);
		$final = array();
		foreach($parents as $v) {
			$final[$v[0]] = $v[1];
		}
		return $final;
	}
	public function getParentCategoriesHitCount($id){
		$fieldName = 'categories';
		$facetResponse = $this->getFacetResponse($fieldName);
		$tree = $this->buildTree($facetResponse->values);
		$treeEnd = $this->getSelectedTreeNode($tree);
		if($treeEnd == null) {
			return $tree['node']->hitCount;
		}
		if($treeEnd['node']->stringValue == $tree['node']->stringValue) {
			return $tree['node']->hitCount;
		}
		$parent = $treeEnd;
		while($parent) {
			if($parent['node']->hierarchyId == $id){
				return $parent['node']->hitCount;
			}
			$parent = $this->getTreeParent($tree, $parent);
		}
		return 0;
	}

	public function getSelectedValueLabel($fieldName, $index=0) {
		if($fieldName == "") {
			return "";
		}
		$svs = $this->getSelectedValues($fieldName);
		if(isset($svs[$index])) {
			return $this->getFacetValueLabel($fieldName, $svs[$index]);
		}
		$facet = $this->getFacetByFieldName($fieldName);
		if($facet != null) {
			if($facet['type'] == 'hierarchical') {
				$facetResponse = $this->getFacetResponse($fieldName);
				$tree = $this->buildTree($facetResponse->values);
				$tree = $this->getSelectedTreeNode($tree);
				$parts = explode('/', $tree['node']->stringValue);
				return $parts[sizeof($parts)-1];
			}
			if($facet['type'] == 'ranged') {
				if(isset($this->facets[$fieldName]['selectedValues'][0])) {
					return $this->facets[$fieldName]['selectedValues'][0];
				}
			}
			if(isset($facet['selectedValues'][0])) {
				return $facet['selectedValues'][0];
			}
			return "";
		}
		return "";
	}
	
	public function getPriceFieldName() {
		return $this->priceFieldName;
	}

	public function getCategoriesKeyLabels() {
		$categoryValueArray = array();
		foreach ($this->getCategories() as $v){
			$label = $this->getCategoryValueLabel($v);
			$categoryValueArray[$label] = $v;
		}
		return $categoryValueArray;
	}

	public function getCategories() {
		return $this->getFacetValues($this->getCategoryFieldName());
	}
	
	public function getPriceRanges() {
		return $this->getFacetValues($this->getPriceFieldName());
	}

    public function getFacetValues($fieldName) {
		return array_keys($this->getFacetKeysValues($fieldName));
    }
	
	public function getFacetLabel($fieldName) {
		if(isset($this->facets[$fieldName])) {
			return $this->facets[$fieldName]['label'];
		}
		return $fieldName;
	}
	
	protected function getFacetValueArray($fieldName, $facetValue) {
        $keyValues = $this->getFacetKeysValues($fieldName);
		if(!isset($keyValues[$facetValue])) {
			throw new \Exception("Requesting an invalid facet values for fieldname: " . $fieldName . ", requested value: " . $facetValue . ", available values . " . implode(',', array_keys($keyValues)));
		}
		$type = $this->getFacetType($fieldName);
		$fv = isset($keyValues[$facetValue]) ? $keyValues[$facetValue] : null;
		switch($type) {
		case 'hierarchical':
			$parts = explode("/", $fv->stringValue);
			return array($parts[sizeof($parts)-1], $parts[0], $fv->hitCount, $fv->selected);
		case 'ranged':
			$from = round($fv->rangeFromInclusive, 2);
			$to = round($fv->rangeToExclusive, 2);
			$valueLabel = $from . ' - ' . $to;
			$paramValue = $fv->stringValue;
			$paramValue = "$from-$to";
			return array($valueLabel, $paramValue, $fv->hitCount, $fv->selected);
			
		default:
			$fv = $keyValues[$facetValue];
			return array($fv->stringValue, $fv->stringValue, $fv->hitCount, $fv->selected);
		}
	}
	
	public function getCategoryValueLabel($facetValue){
		return $this->getFacetValueLabel($this->getCategoryFieldName(), $facetValue);
	}
	
	public function getPriceValueLabel($facetValue) {
		return $this->getFacetValueLabel($this->getPriceFieldName(), $facetValue);
	}
	
	public function getFacetValueLabel($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $label;
    }
	
	public function getCategoryValueCount($facetValue){
		return $this->getFacetValueCount($this->getCategoryFieldName(), $facetValue);
	}
	
	public function getPriceValueCount($facetValue) {
		return $this->getFacetValueCount($this->getPriceFieldName(), $facetValue);
	}

    public function getFacetValueCount($fieldName, $facetValue) {
		list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $hitCount;
    }
	
	public function getCategoryValueId($facetValue) {
		return $this->getFacetValueParameterValue($this->getCategoryFieldName(), $facetValue);
	}
	
	public function getPriceValueParameterValue($facetValue) {
		return $this->getFacetValueParameterValue($this->getPriceFieldName(), $facetValue);
	}

    public function getFacetValueParameterValue($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $parameterValue;
    }
	
	public function isPriceValueSelected($facetValue) {
		return $this->isFacetValueSelected($this->getPriceFieldName(), $facetValue);
	}
	
	public function isFacetValueSelected($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $selected;
	}

	public function getThriftFacets() {
		
		$thriftFacets = array();
		
		foreach($this->facets as $fieldName => $facet) {
			$type = $facet['type'];
			$order = $facet['order'];
			
			$facetRequest = new \com\boxalino\p13n\api\thrift\FacetRequest();
			$facetRequest->fieldName = $fieldName;
			$facetRequest->numerical = $type == 'ranged' ? true : $type == 'numerical' ? true : false;
			$facetRequest->range = $type == 'ranged' ? true : false;
			$facetRequest->boundsOnly = $facet['boundsOnly'];
			$facetRequest->selectedValues = $this->facetSelectedValue($fieldName, $type);
			$facetRequest->sortOrder = isset($order) && $order == 1 ? 1 : 2;
			$thriftFacets[] = $facetRequest;
		}
		
		return $thriftFacets;
	}

    private function facetSelectedValue($fieldName, $option)
    {
        $selectedFacets = array();
		if (isset($this->facets[$fieldName]['selectedValues'])) {
            foreach ($this->facets[$fieldName]['selectedValues'] as $value) {
                $selectedFacet = new \com\boxalino\p13n\api\thrift\FacetValue();
                if ($option == 'ranged') {
                    $rangedValue = explode('-', $value);
                    if ($rangedValue[0] != '*') {
                        $selectedFacet->rangeFromInclusive = $rangedValue[0];
                    }
                    if ($rangedValue[1] != '*') {
                        $selectedFacet->rangeToExclusive = $rangedValue[1];
                    }
                } else {
                    $selectedFacet->stringValue = $value;
                }
                $selectedFacets[] = $selectedFacet;

            }
            return $selectedFacets;
        }
        return;
    }

	public function getParentId($fieldName, $id){
		$hierarchy = array();

		foreach ($this->facetResponse as $response) {
			if($response->fieldName == $fieldName){
				foreach($response->values as $item){
					if($item->hierarchyId == $id){
						$hierarchy = $item->hierarchy;
						if(count($hierarchy) < 4) {
							return 1;
						}
					}
				}
				foreach ($response->values as $item) {
					if (count($item->hierarchy) == count($hierarchy) - 1) {
						if ($item->hierarchy[count($hierarchy) - 2] === $hierarchy[count($hierarchy) - 2]) {
							return $item->hierarchyId;
						}
					}
				}
			}
		}
	}
}
