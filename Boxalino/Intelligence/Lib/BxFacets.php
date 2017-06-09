<?php

namespace com\boxalino\bxclient\v1;

class BxFacets
{
	public $facets = array();
	protected $searchResult = null;

	protected $selectedPriceValues = null;

	protected $parameterPrefix = '';
	
	protected $priceFieldName = 'discountedPrice';
	
	public function setSearchResults($searchResult) {
		$this->searchResult = $searchResult;
	}
	
	public function getCategoryFieldName() {
		return "categories";
	}
	
	private $filters = array();
	
	public function getFilters() {
		return $this->filters;
	}
	
	public function addCategoryFacet($selectedValue=null, $order=2, $maxCount=-1) {
		if($selectedValue) {
			$this->addFacet('category_id', $selectedValue, 'hierarchical', '1', $maxCount);
		}
		$this->addFacet($this->getCategoryFieldName(), null, 'hierarchical', null, $order, false, $maxCount);
	}
	
	public function addPriceRangeFacet($selectedValue=null, $order=2, $label='Price', $fieldName = 'discountedPrice', $maxCount=-1) {
		$this->priceFieldName = $fieldName;
		$this->addRangedFacet($fieldName, $selectedValue, $label, $order, true, $maxCount);
	}
	
	public function addRangedFacet($fieldName, $selectedValue=null, $label=null, $order=2, $boundsOnly=false, $maxCount=-1) {
		$this->addFacet($fieldName, $selectedValue, 'ranged', $label, $order, $boundsOnly, $maxCount);
	}

	public function addFacet($fieldName, $selectedValue=null, $type='string', $label=null, $order=2, $boundsOnly=false, $maxCount=-1) {
		$selectedValues = array();
		if($selectedValue) {
			$selectedValues = is_array($selectedValue) ? $selectedValue : [$selectedValue];
		}
		$this->facets[$fieldName] = array('label'=>$label, 'type'=>$type, 'order'=>$order, 'selectedValues'=>$selectedValues, 'boundsOnly'=>$boundsOnly, 'maxCount'=>$maxCount);

	}
	
	public function setParameterPrefix($parameterPrefix) {
		$this->parameterPrefix = $parameterPrefix;
	}
	
	protected function isCategories($fieldName) {
		return strpos($fieldName, $this->getCategoryFieldName()) !== false ;
	}

    public function getFacetParameterName($fieldName) {
		$parameterName = $fieldName;
		if($this->isCategories($fieldName)) {
			$parameterName = 'category_id';
		}
        return $this->parameterPrefix . $parameterName;
    }

    public function getFieldNames() {
		$fieldNames = array();
		foreach($this->facets as $fieldName => $facet) {
			$facetResponse = $this->getFacetResponse($fieldName);
			if(sizeof($facetResponse->values)>0) {
				$fieldNames[$fieldName] = array('fieldName'=>$fieldName, 'returnedOrder'=> sizeof($fieldNames));
			}
		}

		uasort($fieldNames, function ($a, $b) {
			$aValue = intval($this->getFacetExtraInfo($a['fieldName'], 'order', $a['returnedOrder']));

			if($aValue == 0) {
				$aValue =  $a['returnedOrder'];
			}
			$bValue = intval($this->getFacetExtraInfo($b['fieldName'], 'order', $b['returnedOrder']));
			if($bValue == 0) {
				$bValue =  $b['returnedOrder'];
			}
			return ($aValue < $bValue) ? -1 : 1;
		});
        return array_keys($fieldNames);
    }
	
	public function getDisplayFacets($display, $default=false) {
		$selectedFacets = array();
		foreach($this->getFieldNames() as $fieldName) {
			if($this->getFacetDisplay($fieldName) == $display || ($this->getFacetDisplay($fieldName) == null && $default)) {
				$selectedFacets[] = $fieldName;
			}
		}
		return $selectedFacets;
	}
	
	public function getFacetExtraInfoFacets($extraInfoKey, $extraInfoValue, $default=false, $returnHidden=false) {
		$selectedFacets = array();
		foreach($this->getFieldNames() as $fieldName) {
			if(!$returnHidden && $this->isFacetHidden($fieldName)) {
				continue;
			}
			if($this->getFacetExtraInfo($fieldName, $extraInfoKey) == $extraInfoValue || ($this->getFacetExtraInfo($fieldName, $extraInfoKey) == null && $default)) {
				$selectedFacets[] = $fieldName;
			}
		}
		return $selectedFacets;
	}
	
	public function getLeftFacets($returnHidden=false) {
		return $this->getFacetExtraInfoFacets('position', 'left', true, $returnHidden);
	}
	
	public function getTopFacets($returnHidden=false) {
		return $this->getFacetExtraInfoFacets('position', 'top', false, $returnHidden);
	}
	
	public function getBottomFacets($returnHidden=false) {
		return $this->getFacetExtraInfoFacets('position', 'bottom', false, $returnHidden);
	}
	
	public function getRightFacets($returnHidden=false) {
		return $this->getFacetExtraInfoFacets('position', 'right', false, $returnHidden);
	}
	
	public function getFacetResponseExtraInfo($facetResponse, $extraInfoKey, $defaultExtraInfoValue = null) {
		if($facetResponse) {
			if(is_array($facetResponse->extraInfo) && sizeof($facetResponse->extraInfo) > 0 && isset($facetResponse->extraInfo[$extraInfoKey])) {
				return $facetResponse->extraInfo[$extraInfoKey];
			}
			return $defaultExtraInfoValue;
		}
		return $defaultExtraInfoValue;
	}
	
	public function getFacetResponseDisplay($facetResponse, $defaultDisplay = 'expanded') {
		if($facetResponse) {
			if($facetResponse->display) {
				return $facetResponse->display;
			}
			return $defaultDisplay;
		}
		return $defaultDisplay;
	}
	
	public function getFacetExtraInfo($fieldName, $extraInfoKey, $defaultExtraInfoValue = null) {
		if ($fieldName == $this->getCategoryFieldName()) {
			$fieldName = 'category_id';
		}
		try {
			return $this->getFacetResponseExtraInfo($this->getFacetResponse($fieldName), $extraInfoKey, $defaultExtraInfoValue);
		} catch(\Exception $e) {
			return $defaultExtraInfoValue;
		}
		return $defaultExtraInfoValue;
	}
	
	public function prettyPrintLabel($label, $prettyPrint=false) {
		if($prettyPrint) {
			$label = str_replace('_', ' ', $label);
			$label = str_replace('products', '', $label);
			$label = ucfirst(trim($label));
		}
		return $label;
	}
	
	public function getFacetLabel($fieldName, $language=null, $defaultValue=null, $prettyPrint=false) {
		if(isset($this->facets[$fieldName])) {
			$defaultValue = $this->facets[$fieldName]['label'];
		}
		if($defaultValue == null) {
			$defaultValue = $fieldName;
		}
		if($language != null) {
			$jsonLabel = $this->getFacetExtraInfo($fieldName, "label");
			if($jsonLabel == null) {
				return $this->prettyPrintLabel($defaultValue, $prettyPrint);
			}
			$labels = json_decode($jsonLabel);
			foreach($labels as $label) {
				if($language && $label->language != $language) {
					continue;
				}
				if($label->value != null) {
					return $this->prettyPrintLabel($label->value, $prettyPrint);
				}
			}
		}
			return $this->prettyPrintLabel($defaultValue, $prettyPrint);
	}
	
	public function showFacetValueCounters($fieldName, $defaultValue=true) {
		return $this->getFacetExtraInfo($fieldName, "showCounter", $defaultValue ? "true" : "false") != "false";
	}
	
	public function getFacetIcon($fieldName, $defaultValue=null) {
		return $this->getFacetExtraInfo($fieldName, "icon", $defaultValue);
	}
	
	public function isFacetExpanded($fieldName, $default=true) {
	    $fieldName = $fieldName == $this->getCategoryFieldName() ? 'category_id' : $fieldName;
		$defaultDisplay = $default ? 'expanded' : null;
		return $this->getFacetDisplay($fieldName, $defaultDisplay) == 'expanded';
	}
	
	public function getHideCoverageThreshold($fieldName, $defaultHideCoverageThreshold = 0) {
		$defaultHideCoverageThreshold = $this->getFacetExtraInfo($fieldName, "minDisplayCoverage", $defaultHideCoverageThreshold);
		return $defaultHideCoverageThreshold;
	}
	
	public function getTotalHitCount() {
		return $this->searchResult->totalHitCount;
	}
	
	public function getFacetCoverage($fieldName) {
		$coverage = 0;
		foreach($this->getFacetValues($fieldName) as $facetValue) {
			$coverage += $this->getFacetValueCount($fieldName, $facetValue);
		}
		return $coverage;
	}
	
	public function isFacetHidden($fieldName, $defaultHideCoverageThreshold = 0) {
		if($this->getFacetDisplay($fieldName) == 'hidden') {
			return true;
		}
		$defaultHideCoverageThreshold = $this->getHideCoverageThreshold($fieldName, $defaultHideCoverageThreshold);
		if($defaultHideCoverageThreshold > 0 && sizeof($this->getSelectedValues($fieldName)) == 0) {
			$ratio = $this->getFacetCoverage($fieldName) / $this->getTotalHitCount();
			return $ratio < $defaultHideCoverageThreshold;
		}
		return false;
	}
	
	public function getFacetDisplay($fieldName, $defaultDisplay = 'expanded') {
		try {
			return $this->getFacetResponseDisplay($this->getFacetResponse($fieldName), $defaultDisplay);
		} catch(\Exception $e) {
			return $defaultDisplay;
		}
		return $defaultDisplay;
	}

    protected function getFacetResponse($fieldName) {
        if($this->searchResult != null && $this->searchResult->facetResponses != null) {
			foreach($this->searchResult->facetResponses as $facetResponse) {
				if($facetResponse->fieldName == $fieldName) {
					return $facetResponse;
				}
			}
			throw new \Exception("trying to get facet response on unexisting fieldname " . $fieldName);
		}
        throw new \Exception("trying to get facet response but not facet response set");
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
			$parents = array();
			foreach($response as $node) {
				if(sizeof($node->hierarchy) == 1) {
					$parents[] = $node;
				}
			}
			if(sizeof($parents) == 1) {
				$parents = $parents[0]->hierarchy;
			} else if(sizeof($parents) > 1) {
				$children = array();
				$hitCountSum = 0;
				foreach($parents as $parent) {
					$children[] = $this->buildTree($response, $parent->hierarchy,  $parentLevel);
					$hitCountSum += $children[sizeof($children)-1]['node']->hitCount;
				}
				$root = array();
				$root['stringValue'] = '0/Root';
				$root['hitCount'] = $hitCountSum;
				$root['hierarchyId'] = 0;
				$root['hierarchy'] = array();
				$root['selected'] = false;
				return array('node'=>(object)$root, 'children'=>$children);
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
					if(!isset($parents[$k]) || $parents[$k] != $v) {
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
	
	protected function getFirstNodeWithSeveralChildren($tree, $minCategoryLevel=0) {
		if(sizeof($tree['children']) == 0) {
			return null;
		}
		if(sizeof($tree['children']) > 1 && $minCategoryLevel <= 0) {
			return $tree;
		}
		$bestTree = $tree['children'][0];
		if(sizeof($tree['children']) > 1) {
			foreach($tree['children'] as $node) {
				if($node['node']->hitCount > $bestTree['node']->hitCount) {
					$bestTree = $node;
				}
			}
		}
		return $this->getFirstNodeWithSeveralChildren($bestTree, $minCategoryLevel-1);
	}
	
	public function getFacetSelectedValues($fieldName) {
		$selectedValues = array();
		foreach($this->getFacetKeysValues($fieldName) as $val) {
			if(isset($val->selected) && $val->selected && isset($val->stringValue)) {
				$selectedValues[] = (string) $val->stringValue;
			}
		}
		return $selectedValues;
	}
	
	public function getSelectedTreeNode($tree) {
		$selectedCategoryId = null;
		if(isset($this->facets['category_id'])){
			$selectedCategoryId = $this->facets['category_id']['selectedValues'][0];
		}
		if($selectedCategoryId == null) {
			try {
				$values = $this->getFacetSelectedValues('category_id');
				if(sizeof($values) > 0) {
					$selectedCategoryId = $values[0];
				}
			} catch(\Exception $e) {
				
			}
		}
		if($selectedCategoryId == null) {
			return $tree;
		}
		if(!$tree['node']){
			return null;
		}
		$parts = explode('/', $tree['node']->stringValue);
		if($parts[0] == $selectedCategoryId) {
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
	
	public function getCategoryById($categoryId) {
		$facetResponse = $this->getFacetResponse($this->getCategoryFieldName());
		foreach ($facetResponse->values as $bxFacet) {
			if($bxFacet->hierarchyId == $categoryId) {
				return $categoryId; 
			}
		}
		return null;
	}
	
	protected function getFacetKeysValues($fieldName, $ranking='alphabetical', $minCategoryLevel=0) {
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
			$node = $this->getFirstNodeWithSeveralChildren($tree, $minCategoryLevel);
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
		$overWriteRanking = $this->getFacetExtraInfo($fieldName, "valueorderEnums");
		if($overWriteRanking == "counter") {
			$ranking = 'counter';
		}
		if($overWriteRanking == "alphabetical") {
			$ranking = 'alphabetical';
		}
		if($ranking == 'counter') {
			uasort($facetValues, function ($a, $b) {
				if ($a->hitCount > $b->hitCount) {
					return -1;
				} elseif ($b->hitCount > $a->hitCount) {
					return 1;
				}
				return 0;
			});
		}
		
		$displaySelectedValues = $this->getFacetExtraInfo($fieldName, "displaySelectedValues");
		if($displaySelectedValues == "only") {
			$finalFacetValues = array();
			foreach($facetValues as $k => $v) {
				if($v->selected) {
					$finalFacetValues[$k] = $v;
				}
			}
			$facetValues = empty($finalFacetValues) ? $facetValues : $finalFacetValues;
		}
		if($displaySelectedValues == "top") {
			$finalFacetValues = array();
			foreach($facetValues as $k => $v) {
				if($v->selected) {
					$finalFacetValues[$k] = $v;
				}
			}
			foreach($facetValues as $k => $v) {
				if(!$v->selected) {
					$finalFacetValues[$k] = $v;
				}
			}
			$facetValues = $finalFacetValues;
		}

		$enumDisplaySize = intval($this->getFacetExtraInfo($fieldName, "enumDisplayMaxSize"));
		if($enumDisplaySize > 0 && sizeof($facetValues) > $enumDisplaySize) {
			$enumDisplaySizeMin = intval($this->getFacetExtraInfo($fieldName, "enumDisplaySize"));
			if($enumDisplaySizeMin == 0) {
				$enumDisplaySizeMin = $enumDisplaySize;
			}
			$finalFacetValues = array();
			foreach($facetValues as $k => $v) {
				if(sizeof($finalFacetValues) >= $enumDisplaySizeMin) {
					$v->hidden = true;
				}
				$finalFacetValues[$k] = $v;
			}
			$facetValues = $finalFacetValues;
		}
		
        return $facetValues;
	}
	
	public function getSelectedValues($fieldName) {
		$selectedValues = array();
        try {
			foreach($this->getFacetValues($fieldName) as $key) {
				if($this->isFacetValueSelected($fieldName, $key)) {
					$selectedValues[] = $key;
				}
			}
		} catch(\Exception $e) {
			if(isset($this->facets[$fieldName]['selectedValues'])) {
				return $this->facets[$fieldName]['selectedValues'];
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
		$fieldName = $this->getCategoryFieldName();
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
			if($parts[0] != 0) {
				$parents[] = array($parts[0], $parts[sizeof($parts)-1]);
			}
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
		$fieldName = $this->getCategoryFieldName();
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

	public function getCategories($ranking='alphabetical', $minCategoryLevel=0) {
		return $this->getFacetValues($this->getCategoryFieldName(), $ranking, $minCategoryLevel);
	}
	
	public function getPriceRanges() {
		return $this->getFacetValues($this->getPriceFieldName());
	}

	private $lastSetMinCategoryLevel = 0;
    public function getFacetValues($fieldName, $ranking='alphabetical', $minCategoryLevel=0) {
		$this->lastSetMinCategoryLevel = $minCategoryLevel;
		return array_keys($this->getFacetKeysValues($fieldName, $ranking, $minCategoryLevel));
    }
	
	protected function getFacetValueArray($fieldName, $facetValue) {

		if(($fieldName == $this->priceFieldName) && ($this->selectedPriceValues != null)){
			$from = round($this->selectedPriceValues[0]->rangeFromInclusive, 2);
			$to = round($this->selectedPriceValues[0]->rangeToExclusive, 2);
			$valueLabel = $from . ' - ' . $to;
			$paramValue = "$from-$to";
			return array($valueLabel, $paramValue, null, true, false);
		}

        $keyValues = $this->getFacetKeysValues($fieldName, 'alphabetical', $this->lastSetMinCategoryLevel);

		if(is_array($facetValue)){
			$facetValue = reset($facetValue);
		}
		if(!isset($keyValues[$facetValue]) && $fieldName == $this->getCategoryFieldName()) {
			$facetResponse = $this->getFacetResponse($this->getCategoryFieldName());
			foreach ($facetResponse->values as $bxFacet) {
				if($bxFacet->hierarchyId == $facetValue) {
					$keyValues[$facetValue] = $bxFacet;
				}
			}
		}
		if(!isset($keyValues[$facetValue])) {
			throw new \Exception("Requesting an invalid facet values for fieldname: " . $fieldName . ", requested value: " . $facetValue . ", available values . " . implode(',', array_keys($keyValues)));
		}

		$type = $this->getFacetType($fieldName);
		$fv = isset($keyValues[$facetValue]) ? $keyValues[$facetValue] : null;
		$hidden = isset($fv->hidden) ? $fv->hidden : false;
		switch($type) {
		case 'hierarchical':
			$parts = explode("/", $fv->stringValue);
			return array($parts[sizeof($parts)-1], $parts[0], $fv->hitCount, $fv->selected, $hidden);
		case 'ranged':
			$from = round($fv->rangeFromInclusive, 2);
			$to = round($fv->rangeToExclusive, 2);
			$valueLabel = $from . ' - ' . $to;
			$paramValue = $fv->stringValue;
			$paramValue = "$from-$to";
			return array($valueLabel, $paramValue, $fv->hitCount, $fv->selected, $hidden);
			
		default:
			$fv = $keyValues[$facetValue];
			return array($fv->stringValue, $fv->stringValue, $fv->hitCount, $fv->selected, $hidden);
		}
	}
	
	public function getCategoryValueLabel($facetValue){
		return $this->getFacetValueLabel($this->getCategoryFieldName(), $facetValue);
	}

	public function getSelectedPriceRange(){
		$valueLabel = null;
		if($this->selectedPriceValues !== null && ($this->selectedPriceValues != null)){
			$from = round($this->selectedPriceValues[0]->rangeFromInclusive, 2);
			$to = round($this->selectedPriceValues[0]->rangeToExclusive, 2);
			$valueLabel = $from . '-' . $to;
		}
		return $valueLabel;
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

    public function isFacetValueHidden($fieldName, $facetValue) {
		list($label, $parameterValue, $hitCount, $selected, $hidden) = $this->getFacetValueArray($fieldName, $facetValue);
		return $hidden;
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
			$maxCount = $facet['maxCount'];

			if($fieldName == 'discountedPrice'){
				$this->selectedPriceValues = $this->facetSelectedValue($fieldName, $type);
			}

			$facetRequest = new \com\boxalino\p13n\api\thrift\FacetRequest();
			$facetRequest->fieldName = $fieldName;
			$facetRequest->numerical = $type == 'ranged' ? true : $type == 'numerical' ? true : false;
			$facetRequest->range = $type == 'ranged' ? true : false;
			$facetRequest->boundsOnly = $facet['boundsOnly'];
			$facetRequest->selectedValues = $this->facetSelectedValue($fieldName, $type);
			$facetRequest->sortOrder = isset($order) && $order == 1 ? 1 : 2;
			$facetRequest->maxCount = isset($maxCount) && $maxCount > 0 ? $maxCount : -1;
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
                        $selectedFacet->rangeToExclusive = $rangedValue[1] + 0.01;
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

		foreach ($this->searchResult->facetResponses as $response) {
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
