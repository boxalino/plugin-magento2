<?php

namespace com\boxalino\bxclient\v1;

class BxChooseResponse 
{
	private $response;
	private $bxRequests;
	public function __construct($response, $bxRequests=array()) {
		$this->response = $response;
		$this->bxRequests = $bxRequests;
	}
	
	public function getResponse() {
		return $this->response;
	}
	
	public function getChoiceResponseVariant($choice=null) {
        $id = 0;
		foreach($this->bxRequests as $k => $bxRequest) {
			if($choice != null && $choice == $bxRequest->getChoiceId()) {
				$id = $k;
			}
		}
		return $this->getChoiceIdResponseVariant($id);
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
	
	protected function getFirstPositiveSuggestionSearchResult($variant) {
        if(!isset($variant->searchRelaxation->suggestionsResults)) {
            return null;
        }
		foreach($variant->searchRelaxation->suggestionsResults as $searchResult) {
			if($searchResult->totalHitCount > 0) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getVariantSearchResult($variant, $considerRelaxation=true) {
		$searchResult = $variant->searchResult;
		if($considerRelaxation && $variant->searchResult->totalHitCount == 0) {
			return $this->getFirstPositiveSuggestionSearchResult($variant);
		}
		return $searchResult;
	}
	
	public function getSearchResultHitIds($searchResult) {
		$ids = array();
		if($searchResult) {
			if($searchResult->hits){
				foreach ($searchResult->hits as $item) {
					$ids[] = $item->values['id'][0];
				}
			}else{
				foreach ($searchResult->hitsGroups as $hitGroup){
					$ids[] = $hitGroup->groupValue;
				}
			}
		}
        return $ids;
	}

    public function getHitIds($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		return $this->getSearchResultHitIds($this->getVariantSearchResult($variant, $considerRelaxation));
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

    public function getFacets($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation);
		$facets = $this->getRequestFacets($choice);
		if(empty($facets)){
			return null;
		}
		$facets->setFacetResponse($variant->searchResult->facetResponses);
		return $facets;
    }

    public function getHitFieldValues($fields, $choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		return $this->getSearchHitFieldValues($this->getVariantSearchResult($variant, $considerRelaxation), $fields);
    }
	
	public function getFirstHitFieldValue($field=null, $returnOneValue=true, $hitIndex=0) {
		$fieldNames = null;
		if($field != null) {
			$fieldNames = array($field);
		}
		$count = 0;
		foreach($this->getHitFieldValues($fieldNames) as $id => $fieldValueMap) {
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

    public function getTotalHitCount($choice=null, $considerRelaxation=true) {
		$variant = $this->getChoiceResponseVariant($choice);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation);
		if($searchResult == null) {
			return 0;
		}
		return $searchResult->totalHitCount;
    }
	
	public function areResultsCorrected($choice=null) {
        return $this->getTotalHitCount($choice, false) == 0 && $this->getTotalHitCount($choice) > 0 && $this->areThereSubPhrases() == false;
	}
	
	public function getCorrectedQuery($choice=null) {
		$variant = $this->getChoiceResponseVariant($choice);
		$searchResult = $this->getVariantSearchResult($variant);
		if($searchResult) {
			return $searchResult->queryText;
		}
		return null;
	}
	
	public function areThereSubPhrases($choice=null) {
		$variant = $this->getChoiceResponseVariant($choice);
		return isset($variant->searchRelaxation->subphrasesResults) && sizeof($variant->searchRelaxation->subphrasesResults) > 0;
	}
	
	public function getSubPhrasesQueries($choice=null) {
		if(!$this->areThereSubPhrases($choice)) {
			return array();
		}
		$queries = array();
		$variant = $this->getChoiceResponseVariant($choice);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			$queries[] = $searchResult->queryText;
		}
		return $queries;
	}
	
	protected function getSubPhraseSearchResult($queryText, $choice=null) {
		if(!$this->areThereSubPhrases()) {
			return null;
		}
		$variant = $this->getChoiceResponseVariant($choice);
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			if($searchResult->queryText == $queryText) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getSubPhraseTotalHitCount($queryText, $choice=null) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice);
		if($searchResult) {
			return $searchResult->totalHitCount;
		}
		return 0;
	}

    public function getSubPhraseHitIds($queryText, $choice=null) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice);
		if($searchResult) {
			return $this->getSearchResultHitIds($searchResult);
		}
		return array();
    }

    public function getSubPhraseHitFieldValues($queryText, $fields, $choice=null, $considerRelaxation=true) {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice);
		if($searchResult) {
			return $this->getSearchHitFieldValues($searchResult, $fields);
		}
		return array();
    }
}
