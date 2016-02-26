<?php

namespace com\boxalino\bxclient\v1;

class BxAutocompleteResponse 
{
	private $response;
	private $bxAutocompleteRequest;
	public function __construct($response, $bxAutocompleteRequest=null) {
		$this->response = $response;
		$this->bxAutocompleteRequest = $bxAutocompleteRequest;
	}
	
	public function getResponse() {
		return $this->response;
	}

    public function getPrefixSearchHash() {
        if ($this->getResponse()->prefixSearchResult->totalHitCount > 0) {
            return substr(md5($this->getResponse()->prefixSearchResult->queryText), 0, 10);
        } else {
            return null;
        }
    }
	
	public function getTextualSuggestions() {
		$suggestions = array();
		foreach ($this->getResponse()->hits as $hit) {
			$suggestions[] = $hit->suggestion;
        }
        return $suggestions;
	}
	
	protected function getTextualSuggestionHit($suggestion) {
		foreach ($this->getResponse()->hits as $hit) {
			if($hit->suggestion == $suggestion) {
				return $hit;
			}
		}
		throw new \Exception("unexisting textual suggestion provided " . $suggestion);
	}
	
	public function getTextualSuggestionTotalHitCount($suggestion) {
		$hit = $this->getTextualSuggestionHit($suggestion);
		return $hit->searchResult->totalHitCount;
	}
	
	public function getBxSearchResponse($textualSuggestion = null) {
		$searchResult = $textualSuggestion == null ? $this->getResponse()->prefixSearchResult : $this->getTextualSuggestionHit($textualSuggestion)->searchResult;
		return new \com\boxalino\bxclient\v1\BxChooseResponse($searchResult, $this->bxAutocompleteRequest->getBxSearchRequest());
	}
}
