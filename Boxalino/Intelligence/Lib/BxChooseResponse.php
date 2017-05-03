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
			if($choice == null || $choice == $bxRequest->getChoiceId()) {
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
	
	public function getVariantSearchResult($variant, $considerRelaxation=true, $maxDistance=10, $discardIfSubPhrases = true) {

		$searchResult = $variant->searchResult;
		if($considerRelaxation && $variant->searchResult->totalHitCount == 0 && !($discardIfSubPhrases && $this->areThereSubPhrases())) {
			return $this->getFirstPositiveSuggestionSearchResult($variant, $maxDistance);
		}
		return $searchResult;
	}
	
	public function getSearchResultHitIds($searchResult, $fieldId='id') {
		$ids = array();
		if($searchResult) {
			if($searchResult->hits){
				foreach ($searchResult->hits as $item) {
					$ids[] = $item->values[$fieldId][0];
				}
			}elseif(isset($searchResult->hitsGroups)){
				foreach ($searchResult->hitsGroups as $hitGroup){
					$ids[] = $hitGroup->groupValue;
				}
			}
		}
        return $ids;
	}

    public function getHitIds($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10, $fieldId='id', $discardIfSubPhrases = true) {

		$variant = $this->getChoiceResponseVariant($choice, $count);
		return $this->getSearchResultHitIds($this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance, $discardIfSubPhrases), $fieldId);
    }
	
	public function retrieveHitFieldValues($item, $field, $fields, $hits) {
		$fieldValues = array();
		foreach($this->bxRequests as $bxRequest) {
			$fieldValues = array_merge($fieldValues, $bxRequest->retrieveHitFieldValues($item, $field, $fields, $hits));
		}
		return $fieldValues;
	}
	
	public function getSearchHitFieldValues($searchResult, $fields=null) {
		$fieldValues = array();
		if($searchResult) {
			$hits = $searchResult->hits;
			if($searchResult->hits == null){
				$hits = array();
				foreach ($searchResult->hitsGroups as $hitGroup){
					$hits[] = $hitGroup->hits[0];
				}
			}
			foreach ($hits as $item) {
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
						$fieldValues[$item->values['id'][0]][$field] = $this->retrieveHitFieldValues($item, $field, $searchResult->hits, $finalFields);
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

    public function getFacets($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10, $discardIfSubPhrases = true) {
		
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance, $discardIfSubPhrases);
		$facets = $this->getRequestFacets($choice);

		if(empty($facets) || $searchResult == null){
			return new \com\boxalino\bxclient\v1\BxFacets();;
		}
		$facets->setSearchResults($searchResult);
		return $facets;
    }

    public function getHitFieldValues($fields, $choice=null, $considerRelaxation=true, $count=0, $maxDistance=10, $discardIfSubPhrases = true) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		return $this->getSearchHitFieldValues($this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance, $discardIfSubPhrases), $fields);
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

    public function getTotalHitCount($choice=null, $considerRelaxation=true, $count=0, $maxDistance=10, $discardIfSubPhrases = true) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant, $considerRelaxation, $maxDistance, $discardIfSubPhrases);
		if($searchResult == null) {
			return 0;
		}
		return $searchResult->totalHitCount;
    }
	
	public function areResultsCorrected($choice=null, $count=0, $maxDistance=10) {
        return $this->getTotalHitCount($choice, false, $count) == 0 && $this->getTotalHitCount($choice, true, $count, $maxDistance) > 0 && $this->areThereSubPhrases() == false;
	}
	
	public function areResultsCorrectedAndAlsoProvideSubPhrases($choice=null, $count=0, $maxDistance=10) {
        return $this->getTotalHitCount($choice, false, $count) == 0 && $this->getTotalHitCount($choice, true, $count, $maxDistance, false) > 0 && $this->areThereSubPhrases() == true;
	}
	
	public function getCorrectedQuery($choice=null, $count=0, $maxDistance=10) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		$searchResult = $this->getVariantSearchResult($variant, true, $maxDistance, false);
		if($searchResult) {
			return $searchResult->queryText;
		}
		return null;
	}
	
	public function areThereSubPhrases($choice=null, $count=0, $maxBaseResults=0) {
		$variant = $this->getChoiceResponseVariant($choice, $count);
		return isset($variant->searchRelaxation->subphrasesResults) && sizeof($variant->searchRelaxation->subphrasesResults) > 0 && $this->getTotalHitCount($choice, false, $count) <= $maxBaseResults;
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

    public function getSubPhraseHitIds($queryText, $choice=null, $count=0, $fieldId='id') {
		$searchResult = $this->getSubPhraseSearchResult($queryText, $choice, $count);
		if($searchResult) {
			return $this->getSearchResultHitIds($searchResult, $fieldId);
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
	
	public function toJson($fields) {
		$object = array();
		$object['hits'] = array();
		foreach($this->getHitFieldValues($fields) as $id => $fieldValueMap) {
			$hitFieldValues = array();
			foreach($fieldValueMap as $fieldName => $fieldValues) {
				$hitFieldValues[$fieldName] = array('values'=>$fieldValues);
			}
			$object['hits'][] = array('id'=>$id, 'fieldValues'=>$hitFieldValues);
		}
		return json_encode($object);
	}
}
