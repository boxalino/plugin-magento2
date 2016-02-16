<?php

class BxFacets
{
	protected $facets = array();
	protected $requestFacets = null;
	protected $facetResponse = null;
	protected $parameterPrefix = '';
	
	public function setRequestFacets($requestFacets) {
		$this->requestFacets = $requestFacets;
	}
	
	public function setFacetResponse($facetResponse) {
		$this->facetResponse = $facetResponse;
	}

	public function addFacet($fieldName, $label, $type, $direction=2) {
		$this->facets[$fieldName] = array($label, $type, $direction);
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
        $fieldNames = array();
        foreach($this->requestFacets as $facetResponse) {
            $fieldNames[] = $facetResponse->fieldName;
        }
        return $fieldNames;
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
			list($label, $type, $order) = $this->facets[$fieldName];
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
		if(isset($this->requestFacets['category_id'])) {
			$parts = explode('/', $tree['node']->stringValue);
			if($parts[0] == $this->requestFacets['category_id'][0]) {
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
		return $tree;
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
			if($facet[1] == 'hierarchical') {
				$facetResponse = $this->getFacetResponse($fieldName);
				$tree = $this->buildTree($facetResponse->values);
				$tree = $this->getSelectedTreeNode($tree);
				return $tree && sizeof($tree['node']->hierarchy)>1;
			}
			return isset($this->requestFacets[$fieldName]);
		}
		return false;
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
			if($facet[1] == 'hierarchical') {
				$facetResponse = $this->getFacetResponse($fieldName);
				$tree = $this->buildTree($facetResponse->values);
				$tree = $this->getSelectedTreeNode($tree);
				$parts = explode('/', $tree['node']->stringValue);
				return $parts[sizeof($parts)-1];
			}
			if($facet[1] == 'ranged') {
				if(isset($this->requestFacets[$fieldName][0])) {
					return $this->requestFacets[$fieldName][0];
				}
			}
			return $facet[2];
		}
		return "";
	}

    public function getFacetValues($fieldName) {
		return array_keys($this->getFacetKeysValues($fieldName));
    }
	
	public function getFacetLabel($fieldName) {
		if(isset($this->facets[$fieldName])) {
			return $this->facets[$fieldName][0];
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
	
	public function getFacetValueLabel($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $label;
    }

    public function getFacetValueCount($fieldName, $facetValue) {
		list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $hitCount;
    }

    public function getFacetValueParameterValue($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $parameterValue;
    }
	
	public function isFacetValueSelected($fieldName, $facetValue) {
        list($label, $parameterValue, $hitCount, $selected) = $this->getFacetValueArray($fieldName, $facetValue);
		return $selected;
	}

	public function getThriftFacets() {
		
		$thriftFacets = array();
		
		foreach($this->facets as $fieldName => $facet) {
			list($label, $type, $order) = $facet;
			$facetRequest = new \com\boxalino\p13n\api\thrift\FacetRequest();
			$facetRequest->fieldName = $fieldName;
			$facetRequest->numerical = $type == 'ranged' ? true : $type == 'numerical' ? true : false;
			$facetRequest->range = $type == 'ranged' ? true : false;
			$facetRequest->selectedValues = $this->facetSelectedValue($fieldName, $type);
			$facetRequest->sortOrder = isset($order) && $order == 1 ? 1 : 2;
			$thriftFacets[] = $facetRequest;
		}
		
		return $thriftFacets;
	}

    private function facetSelectedValue($name, $option)
    {
        $selectedFacets = array();
		if (isset($this->requestFacets[$name])) {
            foreach ($this->requestFacets[$name] as $value) {
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
}
