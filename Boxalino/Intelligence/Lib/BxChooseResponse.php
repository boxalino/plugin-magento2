<?php

namespace com\boxalino\bxclient\v1;

class BxChooseResponse 
{
	private $response;
	private $bxRequests;
	public function __construct($response, $bxRequests=array()) {
		$this->response = $response;
		$this->bxRequests = is_array($bxRequests) ? $bxRequests : array($bxRequests);
	}
	
	public function getResponse() {
		return $this->response;
	}
	
	public function getChoiceResponseVariant($choice=null, $count=0) {

		foreach($this->bxRequests as $k => $bxRequest) {
			if($choice != null && $choice == $bxRequest->getChoiceId()) {
				if($count > 0){
					$count--;
					continue;
				}
				return $this->getChoiceIdResponseVariant($k);
			}
		}
	}
	
	protected function getChoiceIdResponseVariant($id=0) {
        $response = $this->getResponse();
		if (!empty($response->variants) && isset($response->variants[$id])) {
            return $response->variants[$id];
		}
		//autocompletion case (no variants)
		if(get_class($response) == 'com\boxalino\p13n\api\thrift\SearchResult') {
			$variant = new \com\boxalino\p13n\api\thrift\Variant();
			$variant->searchResult = $response;
			return $variant;
		}
		throw new \Exception("no variant provided in choice response for variant id $id");
	}
	
	protected function getFirstPositiveSuggestionSearchResult($variant, $maxDistance=10) {
        if(!isset($variant->searchRelaxation->suggestionsResults)) {
            return null;
        }
		foreach($variant->searchRelaxation->suggestionsResults as $searchResult) {
			if($searchResult->totalHitCount > 0) {
				if($searchResult->queryText == "" || $variant->searchResult->queryText == "") {
					continue;
				}
				$distance = levenshtein($searchResult->queryText, $variant->searchResult->queryText);
				if($distance <= $maxDistance && $distance != -1) {
					return $searchResult;
				}
			}
		}
		return null;
	}
	
	public function getVariantSearchResult($variant, $considerRelaxation=true, $maxDistance=10) {

		$searchResult = $variant->searchResult;
		if($considerRelaxation && $variant->searchResult->totalHitCount == 0) {
			return $this->getFirstPositiveSuggestionSearchResult($variant, $maxDistance);
		}
		return $searchResult;
	}
	
	public function getSearchResultHitIds($searchResult) {
		$ids = array();
		if($searchResult) {
			if($searchResult->hits){
				foreach ($searchResult->hits as $item) {
					$ids[] = $item->values['products_group_id'][0];
				}
			}elseif(isset($searchResult->hitsGroups)){
				foreach ($searchResult->hitsGroups as $hitGroup){
					$ids[] = $hitGroup->groupValue;
				}
			}
		}
        return $ids;
	}

    public function getHitIds($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10) {

		$variant = $this->getChoiceResponseVariant($choice, $count);
		return $this->getSearchResultHitIds($this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance));
    }
	
	public function getSearchHitFieldValues($searchResult, $fields=null) {
		$fieldValues = array();
		if($searchResult) {
			foreach ($searchResult->hits as $item) {
				$finalFields = $fields;
				if($finalFields == null) {
					$finalFields = array_keys($item->values);
				}
				foreach ($finalFields as $field) {
					if (isset($item->values[$field])) {
						if (!empty($item->values[$field])) {
							$fieldValues[$item->values['id'][0]][$field] = $item->values[$field];
						}
					}
					if(!isset($fieldValues[$item->values['id'][0]][$field])) {
						$fieldValues[$item->values['id'][0]][$field] = array();
					}
				}
			}
		}
		return $fieldValues;
	}
	
	protected function getRequestFacets($choice=null) {
		if($choice == null) {
			if(isset($this->bxRequests[0])) {
				return $this->bxRequests[0]->getFacets();
			}
			return null;
		}
		foreach($this->bxRequests as $bxRequest) {
			if($bxRequest->getChoiceId() == $choice) {
				return $bxRequest->getFacets();
			}
		}
		return null;
	}

    public function getFacets($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10) {
		
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance);
		$facets = $this->getRequestFacets($choice);

		if(empty($facets) || $searchResult == null){
			return null;
		}
		$facets->setFacetResponse($searchResult->facetResponses);
		return $facets;
    }

    public function getHitFieldValues($fields, $choice=null, $considerRelaxation=true, $count=0, $maxDistance=10) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		return $this->getSearchHitFieldValues($this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance), $fields);
    }
	
	public function getFirstHitFieldValue($field=null, $returnOneValue=true, $hitIndex=0, $choice=null, $count=0, $maxDistance=10) {
		$fieldNames = null;
		if($field != null) {
			$fieldNames = array($field);
		}
		$count = 0;
		foreach($this->getHitFieldValues($fieldNames, $choice, true, $count, $maxDistance) as $id => $fieldValueMap) {
			if($count++ < $hitIndex) {
				continue;
			}
			foreach($fieldValueMap as $fieldName => $fieldValues) {
				if(sizeof($fieldValues)>0) {
					if($returnOneValue) {
						return $fieldValues[0];
					} else {
						return $fieldValues;
					}
				}
			}
		}
		return null;
	}

    public function getTotalHitCount($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance);
		if($searchResult == null) {
			return 0;
		}
		return $searchResult->totalHitCount;
    }
	
	public function areResultsCorrected($choice=null, $count=0, $maxDistance=10) {
        return $this->getTotalHitCount($choice, false, $count) == 0 && $this->getTotalHitCount($choice, true, $count, $maxDistance) > 0 && $this->areThereSubPhrases() == false;
	}
	
	public function getCorrectedQuery($choice=null, $count=0) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant);
		if($searchResult) {
			return $searchResult->queryText;
		}
		return null;
	}
	
	public function areThereSubPhrases($choice=null, $count=0) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		return isset($variant->searchRelaxation->subphrasesResults) && sizeof($variant->searchRelaxation->subphrasesResults) > 0;
	}
	
	public function getSubPhrasesQueries($choice=null, $count=0) {
		if(!$this->areThereSubPhrases($choice, $count)) {
			return array();
		}
		$queries = array();
		$variant = $this->getChoiceResponseVariant($choice, $count);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			$queries[] = $searchResult->queryText;
		}
		return $queries;
	}
	
	protected function getSubPhraseSearchResult($queryText, $choice=null, $count=0) {
		if(!$this->areThereSubPhrases($choice, $count)) {
			return null;
		}
		$variant = $this->getChoiceResponseVariant($choice, $count);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			if($searchResult->queryText == $queryText) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getSubPhraseTotalHitCount($queryText, $choice=null, $count=0) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice, $count);
		if($searchResult) {
			return $searchResult->totalHitCount;
		}
		return 0;
	}

    public function getSubPhraseHitIds($queryText, $choice=null, $count=0) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice, $count);
		if($searchResult) {
			return $this->getSearchResultHitIds($searchResult);
		}
		return array();
    }

    public function getSubPhraseHitFieldValues($queryText, $fields, $choice=null, $considerRelaxation=true, $count=0) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice, $count);
		if($searchResult) {
			return $this->getSearchHitFieldValues($searchResult, $fields);
		}
		return array();
    }
}
