<?php

namespace com\boxalino\bxclient\v1;

class BxRequest
{
	protected $language;
	protected $groupBy;
	protected $choiceId;
	protected $min;
	protected $max;
	protected $withRelaxation;
	
	protected $indexId = null;
	protected $requestMap = null;
	protected $returnFields = array();
	protected $offset = 0;
	protected $queryText = "";
	protected $bxFacets = null;
	protected $bxSortFields = null;
	protected $bxFilters = array();
	protected $orFilters = false;
    protected $hitsGroupsAsHits = null;
    protected $groupFacets = null;

    protected $requestContextParameters = array();
	
	public function __construct($language, $choiceId, $max=10, $min=0) {
		if($choiceId == ''){
			throw new \Exception('BxRequest created with null choiceId');
		}
		$this->language = $language;
		$this->choiceId = $choiceId;
		$this->min = (float)$min;
		$this->max = (float)$max;
		if($this->max == 0) {
			$this->max = 1;
		}
		$this->withRelaxation = $choiceId == 'search';
	}
	
	public function getWithRelaxation() {
		return $this->withRelaxation;
	}
	
	public function setWithRelaxation($withRelaxation) {
		$this->withRelaxation = $withRelaxation;
	}
	
	public function getReturnFields() {
		return $this->returnFields;
	}
	
	public function setReturnFields($returnFields) {
		$this->returnFields = $returnFields;
	}
	
	public function getOffset() {
		return $this->offset;
	}
	
	public function setOffset($offset) {
		$this->offset = $offset;
	}
	
	public function getQuerytext() {
		return $this->queryText;
	}
	
	public function setQuerytext($queryText) {
		$this->queryText = $queryText;
	}
	
	public function getFacets() {
		return $this->bxFacets;
	}
	
	public function setFacets($bxFacets) {
		$this->bxFacets = $bxFacets;
	}
	
	public function getSortFields() {
		return $this->bxSortFields;
	}
	
	public function setSortFields($bxSortFields) {
		$this->bxSortFields = $bxSortFields;
	}
	
	public function getFilters() {
		$filters = $this->bxFilters;
		if($this->getFacets()) {
			foreach($this->getFacets()->getFilters() as $filter) {
				$filters[] = $filter;
			}
		}
		return $this->bxFilters;
	}
	
	public function setFilters($bxFilters) {
		$this->bxFilters = $bxFilters;
	}
	
	public function addFilter($bxFilter) {
		$this->bxFilters[$bxFilter->getFieldName()] = $bxFilter;
	}
	
	public function getOrFilters() {
		return $this->orFilters;
	}
	
	public function setOrFilters($orFilters) {
		$this->orFilters = $orFilters;
	}
	
	public function addSortField($field, $reverse = false) {
		if($this->bxSortFields == null) {
			$this->bxSortFields = new \com\boxalino\bxclient\v1\BxSortFields();
		}
		$this->bxSortFields->push($field, $reverse);
	}
	
	public function getChoiceId() {
		return $this->choiceId;
	}
	
	public function setChoiceId($choiceId) {
		$this->choiceId = $choiceId;
	}
	
	public function getMax() {
		return $this->max;
	}
	
	public function setMax($max) {
		$this->max = $max;
	}

	public function getMin() {
		return $this->min;
	}
	
	public function setMin($min) {
		$this->min = $min;
	}

	public function getIndexId() {
		return $this->indexId;
	}
	
	public function setIndexId($indexId) {
		$this->indexId = $indexId;
		foreach($this->contextItems as $k => $contextItem) {
			if($contextItem->indexId == null) {
				$this->contextItems[$k]->indexId = $indexId;
			}
		}
	}
	
	public function setDefaultIndexId($indexId) {
		if($this->indexId == null) {
			$this->setIndexId($indexId);
		}
	}
	
	public function setDefaultRequestMap($requestMap) {
		if($this->requestMap == null) {
			$this->requestMap = $requestMap;
		}
	}

	public function getLanguage() {
		return $this->language;
	}
	
	public function setLanguage($language) {
		$this->language = $language;
	}

	public function getGroupBy(){
		return $this->groupBy;
	}

	public function setGroupBy($groupBy){
		$this->groupBy = $groupBy;
	}

    public function setHitsGroupsAsHits($groupsAsHits) {
        $this->hitsGroupsAsHits = $groupsAsHits;
    }

    public function setGroupFacets($groupFacets) {
        $this->groupFacets = $groupFacets;
    }

	public function getSimpleSearchQuery() {
		
		$searchQuery = new \com\boxalino\p13n\api\thrift\SimpleSearchQuery();
		$searchQuery->indexId = $this->getIndexId();
		$searchQuery->language = $this->getLanguage();
		$searchQuery->returnFields = $this->getReturnFields();
		$searchQuery->offset = $this->getOffset();
		$searchQuery->hitCount = $this->getMax();
		$searchQuery->queryText = $this->getQueryText();
		$searchQuery->groupFacets = is_null($this->groupFacets) ? false : $this->groupFacets;
		$searchQuery->groupBy = $this->groupBy;
        if(!is_null($this->hitsGroupsAsHits)) {
            $searchQuery->hitsGroupsAsHits = $this->hitsGroupsAsHits;
        }
		if(sizeof($this->getFilters()) > 0) {
			$searchQuery->filters = array();
			foreach($this->getFilters() as $filter) {
				$searchQuery->filters[] = $filter->getThriftFilter();
			}
		}
		$searchQuery->orFilters = $this->getOrFilters();
		if($this->getFacets()) {
			$searchQuery->facetRequests = $this->getFacets()->getThriftFacets();
		}
		if($this->getSortFields()) {
			$searchQuery->sortFields = $this->getSortFields()->getThriftSortFields();
		}

		return $searchQuery;
	}
	
	protected $contextItems = array();
	public function setProductContext($fieldName, $contextItemId, $role = 'mainProduct', $relatedProducts = array(), $relatedProductField = 'id') {

		$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
		$contextItem->indexId = $this->getIndexId();
		$contextItem->fieldName = $fieldName;
		$contextItem->contextItemId = $contextItemId;
		$contextItem->role = $role;
		$this->contextItems[] = $contextItem;
		$this->addRelatedProducts($relatedProducts, $relatedProductField);
	}

	public function setBasketProductWithPrices($fieldName, $basketContent, $role = 'mainProduct', $subRole = 'mainProduct', $relatedProducts = array(), $relatedProductField='id') {
		if ($basketContent !== false && count($basketContent)) {
			
			// Sort basket content by price
			usort($basketContent, function ($a, $b) {
				if ($a['price'] > $b['price']) {
					return -1;
				} elseif ($b['price'] > $a['price']) {
					return 1;
				}
				return 0;
			});

			$basketItem = array_shift($basketContent);

			$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
			$contextItem->indexId = $this->getIndexId();
			$contextItem->fieldName = $fieldName;
			$contextItem->contextItemId = $basketItem['id'];
			$contextItem->role = $role;

			$this->contextItems[] = $contextItem;

			foreach ($basketContent as $basketItem) {
				$contextItem = new \com\boxalino\p13n\api\thrift\ContextItem();
				$contextItem->indexId = $this->getIndexId();
				$contextItem->fieldName = $fieldName;
				$contextItem->contextItemId = $basketItem['id'];
				$contextItem->role = $subRole;

				$this->contextItems[] = $contextItem;
			}
		}
		$this->addRelatedProducts($relatedProducts, $relatedProductField);
	}

	public function addRelatedProducts($relatedProducts, $relatedProductField='id') {

        foreach ($relatedProducts as $productId => $related) {
            $key = "bx_{$this->choiceId}_$productId";
            $this->requestContextParameters[$key] = $related;
        }
    }
	
	public function getContextItems() {
		return $this->contextItems;
	}
	
	public function getRequestContextParameters() {
		return $this->requestContextParameters;
	}
	
	public function retrieveHitFieldValues($item, $field, $items, $fields) {
		return array();
	}

}
