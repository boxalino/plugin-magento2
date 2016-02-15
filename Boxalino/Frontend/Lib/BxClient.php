<?php

class BxClient
{
	private $account;
	private $isDev;
	private $host;
	private $p13n_username;
	private $p13n_password;
	private $domain;
	private $language;
	private $additionalFields;
	private $p13n;
	private $facets;
	
	private $filters = array();
	private $autocompleteResponse = null;
	private $searchResponse = null;
	private $recommendationsResponse = null;
	
	
    const VISITOR_COOKIE_TIME = 31536000;

	public function __construct($account, $isDev, $host, $p13n_username, $p13n_password, $domain, $language, $additionalFields) {
		$this->account = $account;
		$this->isDev = $isDev;
		$this->host = $host;
		if($this->host == null) {
			$this->host = "cdn.bx-cloud.com";
		}
		$this->p13n_username = $p13n_username;
		if($this->p13n_username == null) {
			$this->p13n_username = "ibrows";
		}
		$this->p13n_password = $p13n_password;
		if($this->p13n_password == null) {
			$this->p13n_password = "cfdjermluubsrvkl";
		}
		$this->domain = $domain;
		$this->language = $language;
		$this->additionalFields = $additionalFields;
		$this->p13n = new \Boxalino\Frontend\Lib\vendor\Thrift\HttpP13n();
		
		$this->facets = new \BxFacets();
	}

    private $count = 0;
    public function getCount() {
        return $this->count;
    }

    public function incrementCount() {
        $this->count++;
    }

    /**
     * @param string $field field name for filter
     * @param int $hierarchyId names of categories in hierarchy
     * @param int $hierarchy names of categories in hierarchy
     * @param string|null $lang
     *
     */
    public function addFilterHierarchy($field, $hierarchyId, $hierarchy, $localized = false)
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();

        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }
        
        $filter->hierarchyId = $hierarchyId;
        $filter->hierarchy = $hierarchy;

        $this->filters[] = $filter;
    }

    /**
     * @param string $field field name for filter
     * @param mixed $value filter value
     * @param string|null $lang
     *
     */
    public function addFilter($field, $value, $localized = false, $prefix = 'products_', $bodyName = 'description')
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();
		
		if ($field == $bodyName) {
			$field = 'body';
		} else {
			$field = $prefix . $field;
		}
		
        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }

        if (is_array($value)) {
            $filter->stringValues = $value;
        } else {
            $filter->stringValues = array($value);
        }

        $this->filters[] = $filter;
    }
	
	public function addBxFilter($bxFilter) {
        $this->filters[] = $bxFilter->getThriftFilter();
	}
	
	
    public function addFilterCategory($categoryId, $categoryName)
    {
		$filter = new \com\boxalino\p13n\api\thrift\Filter();

		$filter->fieldName = 'categories';

		$filter->hierarchyId = $categoryId;
		$filter->hierarchy = array($categoryName);

		$this->filters[] = $filter;
    }

    /**
     * @param string $field field name for filter
     * @param number $from param from
     * @param number $to param from
     * @param string|null $lang
     *
     */
    public function addFilterFromTo($field, $from, $to, $localized = false)
    {
        $filter = new \com\boxalino\p13n\api\thrift\Filter();

		if ($field == 'price') {
			$field = 'discountedPrice';
		}

        if ($localized) {
            $filter->fieldName = $field . '_' . $this->language;
        } else {
            $filter->fieldName = $field;
        }

        $filter->rangeFrom = $from;
        $filter->rangeTo = $to;

        $this->filters[] = $filter;
    }
	
	public function getAccount() {
		if($this->isDev) {
			return $this->account . '_dev';
		}
		return $this->account;
	}
	
	private function getSessionAndProfile() {
		if (empty($_COOKIE['cems'])) {
            $sessionid = session_id();
            if (empty($sessionid)) {
                session_start();
                $sessionid = session_id();
            }
        } else {
            $sessionid = $_COOKIE['cems'];
        }

        if (empty($_COOKIE['cemv'])) {
            $profileid = '';
            if (function_exists('openssl_random_pseudo_bytes')) {
                $profileid = bin2hex(openssl_random_pseudo_bytes(16));
            }
            if (empty($profileid)) {
                $profileid = uniqid('', true);
            }
        } else {
            $profileid = $_COOKIE['cemv'];
        }

        // Refresh cookies
        if (empty($this->domain)) {
            setcookie('cems', $sessionid, 0);
            setcookie('cemv', $profileid, time() + self::VISITOR_COOKIE_TIME);
        } else {
            setcookie('cems', $sessionid, 0, '/', $this->domain);
            setcookie('cemv', $profileid, time() + 1800, '/', self::VISITOR_COOKIE_TIME);
        }
		
		return array($sessionid, $profileid);
	}
	
	private function getUserRecord() {
		$userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
        $userRecord->username = $this->getAccount();
        return $userRecord;
	}
	
	private function getSimpleSearchQuery($returnFields, $hitCount, $queryText, $bxFacets = array(), $bxSortFields = null, $offset = 0) {
		$searchQuery = new \com\boxalino\p13n\api\thrift\SimpleSearchQuery();
        $searchQuery->indexId = $this->getAccount();
        $searchQuery->language = $this->language;
        $searchQuery->returnFields = $returnFields;
        $searchQuery->offset = $offset;
        $searchQuery->hitCount = $hitCount;
        $searchQuery->queryText = $queryText;
		if($bxFacets) {
			$searchQuery->facetRequests = $bxFacets->getThriftFacets();
		}
		$searchQuery->filters = $this->filters;
		if($bxSortFields) {
			$searchQuery->sortFields = $bxSortFields->getThriftSortFields();
		}
		return $searchQuery;
	}
	
	private function getCategoryFacet() {
		$facet = new \com\boxalino\p13n\api\thrift\FacetRequest();
		$facet->fieldName = 'categories';
		$facet->numerical = false;
		$facet->range = false;
		return $facet;
	}
	
	private function getAutocompleteQuery($queryText, $suggestionsHitCount) {
		$autocompleteQuery = new \com\boxalino\p13n\api\thrift\AutocompleteQuery();
        $autocompleteQuery->indexId = $this->getAccount();
        $autocompleteQuery->language = $this->language;
        $autocompleteQuery->queryText = $queryText;
        $autocompleteQuery->suggestionsHitCount = $suggestionsHitCount;
        $autocompleteQuery->highlight = true;
        $autocompleteQuery->highlightPre = '<em>';
        $autocompleteQuery->highlightPost = '</em>';
		return $autocompleteQuery;
	}
	
    private function getP13n()
    {
        $this->p13n->setHost($this->host);
        $this->p13n->setAuthorization($this->p13n_username, $this->p13n_password);
        return $this->p13n;
    }
	
	public function getChoiceRequest($inquiries, $requestContext = null) {
		
		$choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();

        list($sessionid, $profileid) = $this->getSessionAndProfile();
        
		$choiceRequest->userRecord = $this->getUserRecord();
		$choiceRequest->profileId = $profileid;
		$choiceRequest->inquiries = $inquiries;
		if($requestContext == null) {
			$requestContext = $this->getRequestContext();
		}
		$choiceRequest->requestContext = $requestContext;

        return $choiceRequest;
	}
	
	public function search($queryText, $hitCount = 10, $returnFields = array(), $searchChoice = 'search', $bxFacets = null, $offset = 0, $bxSortFields=null, $withRelaxation = true) {
		
		$simpleSearchQuery = $this->getSimpleSearchQuery($returnFields, $hitCount, $queryText, $bxFacets, $bxSortFields, $offset);
		
		$choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
		$choiceInquiry->choiceId = $searchChoice;
        $choiceInquiry->simpleSearchQuery = $simpleSearchQuery;
        $choiceInquiry->withRelaxation = $withRelaxation;
		
		$choiceRequest = $this->getChoiceRequest(array($choiceInquiry));
		
		$p13n = $this->getP13n();
		$this->searchResponse = $p13n->choose($choiceRequest);
	}
	
	protected function getIP()
    {
        $ip = null;
        $clientip = @$_SERVER['HTTP_CLIENT_IP'];
        $forwardedip = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        if (filter_var($clientip, FILTER_VALIDATE_IP)) {
            $ip = $clientip;
        } elseif (filter_var($forwardedip, FILTER_VALIDATE_IP)) {
            $ip = $forwardedip;
        } else {
            $ip = @$_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
	
    protected function getCurrentURL()
    {
        $protocol = strpos(strtolower(@$_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
        $hostname = @$_SERVER['HTTP_HOST'];
        $requesturi = @$_SERVER['REQUEST_URI'];

        return $protocol . '://' . $hostname . $requesturi;
    }
	
	protected function getRequestContext()
    {
        list($sessionid, $profileid) = $this->getSessionAndProfile();
		
        $requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
        $requestContext->parameters = array(
            'User-Agent'     => array(@$_SERVER['HTTP_USER_AGENT']),
            'User-Host'      => array($this->getIP()),
            'User-SessionId' => array($sessionid),
            'User-Referer'   => array(@$_SERVER['HTTP_REFERER']),
            'User-URL'       => array($this->getCurrentURL())
        );

        if (isset($_REQUEST['p13nRequestContext']) && is_array($_REQUEST['p13nRequestContext'])) {
            $requestContext->parameters = array_merge(
                $_REQUEST['p13nRequestContext'],
                $requestContext->parameters
            );
        }

        return $requestContext;
    }
	
	protected function recommend($bxRecommendations, $returnFields = array(), $bxFacets = null, $bxSortFields=null, $queryText=null) {
		
		$choiceInquiries = array();
		
		$requestContext = $this->getRequestContext();
		
		$contextItems = array();
		
		foreach($bxRecommendations as $bxRecommendation) {
			$searchQuery = $this->getSimpleSearchQuery($returnFields, $bxRecommendation->getMax(), $queryText, $bxFacets, $bxSortFields);
			
			$choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
			$choiceInquiry->choiceId = $bxRecommendation->getChoiceId();
			$choiceInquiry->simpleSearchQuery = $searchQuery;
			$choiceInquiry->contextItems = $contextItems;
			$choiceInquiry->minHitCount = $bxRecommendation->getMin();
			
			$choiceInquiries[] = $choiceInquiry;
		}
		
		$choiceRequest = $this->getChoiceRequest($choiceInquiries, $requestContext);
		
		$this->recommendationsResponse = $this->choose($choiceRequest);
	}
	
	public function getChoiceRecommendations($choiceId, $bxRecommendations, $returnFields = array()) {
		if(!$this->recommendationsResponse) {
			$this->recommend($bxRecommendations, $returnFields);
		}
		
		foreach ($recommendationsResponse->variants as $variantId => $variant) {
			$name = $bxRecommendations[$variantId];
			if($choiceId == $bxRecommendations[$variantId]->getChoiceId()) {
				foreach ($variant->searchResult->hits as $item) {
					$result = array();
					foreach ($item->values as $key => $value) {
						if (is_array($value) && count($value) == 1) {
							$result[$key] = array_shift($value);
						} else {
							$result[$key] = $value;
						}
					}
					if (!isset($result['name']) && isset($result['title'])) {
						$result['name'] = $result['title'];
					}

					$result['_rule'] = $name . ':' . $variant->scenarioId . ':' . $variant->variantId;
					$result['_choice'] = $name;
					$result['_scenario'] = $variant->scenarioId;
					$result['_variant'] = $variant->variantId;
					return $result;
				}
			}
		}
		return array();
	}
	
	private function choose($choiceRequest) {
		
		try {
			return $this->p13n->choose($choiceRequest);
		} catch(\Exception $e) {
			if(strpos($e->getMessage(), 'choice not found') !== false) {
				$parts = explode('choice not found', $e->getMessage());
				$pieces = explode('	at ', $parts[1]);
				$choiceId = str_replace(':', '', trim($pieces[0]));
				throw new \Exception("Configuration not live on account " . $this->getAccount() . ": choice $choiceId doesn't exist");
			}
			throw $e;
		}
	}
	
	public function isSearchDone() {
		return $this->searchResponse != null;
	}
	
	public function getSearchResponse($func="getSearchResponse") {
		if($this->searchResponse == null) {
			throw new \Exception("$func called before any call to autocomplete method");
		}
		return $this->searchResponse;
	}
	
	public function getSearchResponseVariant($func="getSearchResponseVariant") {
        $response = $this->getSearchResponse($func);
		if (!empty($response->variants)) {
            foreach ($response->variants as $variant) {
				return $variant;
			}
		}
		throw new \Exception("no variant provided in choice response (caller: $func)");
	}
	
	public function getAdditionalData() {
		$result = array();
		$variant = $this->getSearchResponseVariant("getAdditionalData");
		foreach ($variant->searchResult->hits as $item) {
			foreach ($this->additionalFields as $field) {
				if (isset($item->values[$field])) {
					if (!empty($item->values[$field])) {
						$result[$item->values['id'][0]][$field] = $item->values[$field];
					}
				}
			}
		}
		return $result;
    }
	
    public function autocomplete($queryText, $suggestionsHitCount, $hitCount = 0, $returnFields = array(), $autocompleteChoice = 'autocomplete', $searchChoice = 'search')
    {
        $searchQuery = $this->getSimpleSearchQuery($returnFields, $hitCount, $queryText);
		
		list($sessionid, $profileid) = $this->getSessionAndProfile();
        
		$autocompleteRequest = new \com\boxalino\p13n\api\thrift\AutocompleteRequest();
		$autocompleteRequest->userRecord = $this->getUserRecord();
		$autocompleteRequest->profileId = $profileid;
		$autocompleteRequest->choiceId = $autocompleteChoice;
        $autocompleteRequest->searchQuery = $searchQuery;
        $autocompleteRequest->searchChoiceId = $searchChoice;
		$autocompleteRequest->autocompleteQuery = $this->getAutocompleteQuery($queryText, $suggestionsHitCount);
        
		$p13n = $this->getP13n();
		$this->autocompleteResponse = $p13n->autocomplete($autocompleteRequest);

    }
	
	public function getAutocompleteResponse() {
		if($this->autocompleteResponse == null) {
			throw new \Exception("getAutocompleteResponse called before any call to autocomplete method");
		}
		return $this->autocompleteResponse;
	}

    public function getACPrefixSearchHash() {
        if ($this->getAutocompleteResponse()->prefixSearchResult->totalHitCount > 0) {
            return substr(md5($this->getAutocompleteResponse()->prefixSearchResult->queryText), 0, 10);
        } else {
            return null;
        }
    }
	
	public function getAutocompleteTextualSuggestions() {
		$suggestions = array();
		foreach ($this->getAutocompleteResponse()->hits as $hit) {
			$suggestions[] = $hit->suggestion;
        }
        return $suggestions;
	}
	
	protected function getAutocompleteTextualSuggestionHit($suggestion) {
		foreach ($this->getAutocompleteResponse()->hits as $hit) {
			if($hit->suggestion == $suggestion) {
				return $hit;
			}
		}
		throw new \Exception("unexisting textual suggestion provided " . $suggestion);
	}
	
	public function getAutocompleteTextualSuggestionTotalHitCount($suggestion) {
		$hit = $this->getAutocompleteTextualSuggestionHit($suggestion);
		return $hit->searchResult->totalHitCount;
	}
	
	public function getAutocompleteProducts($fields, $suggestion=null) {
		$searchResult = $suggestion == null ? $this->getAutocompleteResponse()->prefixSearchResult : $this->getAutocompleteTextualSuggestionHit($suggestion)->searchResult;
		
		$products = array();
		foreach($searchResult->hits as $item) {
			$values = array();
			foreach($fields as $field) {
				if(isset($item->values[$field])) {
					$values[$field] = $item->values[$field];
				} else {
					$values[$field] = array();
				}
			}
			$k = isset($item->values['id'][0]) ? $item->values['id'][0] : sizeof($products);
			$products[$k] = $values;
		}
		return $products;
	}

    /*private function cmpFacets($a, $b)
    {
        if ($a['hits'] == $b['hits']) {
            return 0;
        }
        return ($a['hits'] > $b['hits']) ? -1 : 1;
    }*/
	
	public function areResultsCorrected() {
        return $this->getTotalHitCount(false) == 0 && $this->getRelaxationTotalHitCount() > 0 && $this->areThereSubPhrases() == false;
	}
	
	public function getCorrectedQuery() {
        $variant = $this->getSearchResponseVariant("getRelaxationTotalHitCount");
		$searchResult = $this->getFirstPositiveSuggestionSearchResult($variant);
		if($searchResult) {
			return $searchResult->queryText;
		}
		return null;
	}

    public function getTotalHitCount($considerRelxation=true)
    {
		$variant = $this->getSearchResponseVariant("getTotalHitCount");
		$count = $variant->searchResult->totalHitCount;
		if($considerRelxation && $this->areResultsCorrected()) {
			return $this->getRelaxationTotalHitCount();
		}
        return $count;
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
	
	public function getRelaxationTotalHitCount() {
		$variant = $this->getSearchResponseVariant("getRelaxationTotalHitCount");
		$searchResult = $this->getFirstPositiveSuggestionSearchResult($variant);
		if($searchResult) {
			return $searchResult->totalHitCount;
		}
		return 0;
	}

    public function getEntitiesIds($entityIdFieldName, $considerRelxation=true)
    {
		$variant = $this->getSearchResponseVariant("getEntitiesIds");
        $result = $this->getSearchResultEntitiesIds($variant->searchResult, $entityIdFieldName);
		if($considerRelxation && $this->areResultsCorrected()) {
			return $this->getRelaxationEntitiesIds($entityIdFieldName);
		}

        return $result;
    }

    public function getRelaxationEntitiesIds($entityIdFieldName)
    {
		$variant = $this->getSearchResponseVariant("getRelaxationEntitiesIds");
		$searchResult = $this->getFirstPositiveSuggestionSearchResult($variant);
		if($searchResult) {
			return $this->getSearchResultEntitiesIds($searchResult, $entityIdFieldName);
		}
		return array();
    }
	
	protected function getSearchResultEntitiesIds($searchResult, $entityIdFieldName) {
		$result = array();
		foreach ($searchResult->hits as $item) {
			if(!isset($item->values[$entityIdFieldName])) {
				throw new \Exception("the requested item property $entityIdFieldName was not returned: " . implode(',', array_keys($item->values)));
			}
			if(!isset($item->values[$entityIdFieldName][0])) {
				//throw new \Exception("the requested item property $entityIdFieldName was not set to any value for item: " . json_encode($item->values));
				$entityIdFieldName = 'id';
			}
			$result[] = $item->values[$entityIdFieldName][0];
		}
		return $result;
	}
	
	public function areThereSubPhrases() {
		$variant = $this->getSearchResponseVariant("areThereSubPhrases");
		return isset($variant->searchRelaxation->subphrasesResults) && sizeof($variant->searchRelaxation->subphrasesResults) > 0;
	}
	
	public function getSubPhrasesQueries() {
		if(!$this->areThereSubPhrases()) {
			return array();
		}
		$queries = array();
		$variant = $this->getSearchResponseVariant("getSubPhrasesQueries");
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			$queries[] = $searchResult->queryText;
		}
		return $queries;
	}
	
	protected function getSubPhraseSearchResult($queryText) {
		if(!$this->areThereSubPhrases()) {
			return null;
		}
		$variant = $this->getSearchResponseVariant("getSubPhraseSearchResult");
		foreach($variant->searchRelaxation->subphrasesResults as $searchResult) {
			if($searchResult->queryText == $queryText) {
				return $searchResult;
			}
		}
		return null;
	}
	
	public function getSubPhraseTotalHitCount($queryText) {
		$searchResult = $this->getSubPhraseSearchResult($queryText);
		if($searchResult) {
			return $searchResult->totalHitCount;
		}
		return 0;
	}

    public function getSubPhraseEntitiesIds($queryText, $entityIdFieldName)
    {
		$searchResult = $this->getSubPhraseSearchResult($queryText);
		if($searchResult) {
			return $this->getSearchResultEntitiesIds($searchResult, $entityIdFieldName);
		}
		return array();
    }

    public function getFacetsData()
    {
        if($this->areResultsCorrected()) {
            $variant = $this->getSearchResponseVariant("getFacetsData");
            $searchResult = $this->getFirstPositiveSuggestionSearchResult($variant);
            $facets = $searchResult->facetResponses;
            return $this->getFacetResponsesData($facets);
        }
        $variant = $this->getSearchResponseVariant("getFacetsData");
		$facets = $variant->searchResult->facetResponses;
		return $this->getFacetResponsesData($facets);
    }
	
	public function setBxFacets($facets) {
		$this->facets = $facets;
	}
	
	public function getBxFacets() {
		return $this->facets;
	}

    public function getFacets() {
        if($this->areResultsCorrected()) {
            $variant = $this->getSearchResponseVariant("getFacetsData");
            $searchResult = $this->getFirstPositiveSuggestionSearchResult($variant);
            $facets = $searchResult->facetResponses;
            return $this->getFacetResponsesData($facets);
        }
        $variant = $this->getSearchResponseVariant("getFacetsData");
        $this->facets->setFacetResponse($variant->searchResult->facetResponses);
		return $this->facets;
    }

    protected function getFacetResponsesData($facets) {
        $preparedFacets = array();
        foreach ($facets as $facet) {
            if (!empty($facet->values)) {
                $filter[$facet->fieldName] = array();
                foreach ($facet->values as $value) {
                    $param['stringValue'] = $value->stringValue;
                    $param['hitCount'] = $value->hitCount;
                    $param['rangeFromInclusive'] = $value->rangeFromInclusive;
                    $param['rangeToExclusive'] = $value->rangeToExclusive;
                    $param['hierarchyId'] = $value->hierarchyId;
                    $param['hierarchy'] = $value->hierarchy;
                    $param['selected'] = $value->selected;
                    $filter[$facet->fieldName][] = $param;
                }
                $preparedFacets = $filter;
            }
        }
        return $preparedFacets;
    }

    /*
	public function printData()
    {
        $results = array();
        $response = $this->getSearchResponse("printData");
        foreach ($response->variants as $variant) {
            $searchResult = $variant->searchResult;
            foreach ($searchResult->hits as $item) {
                $result = array();
                foreach ($item->values as $key => $value) {
                    if (is_array($value) && count($value) == 1) {
                        $result[$key] = array_shift($value);
                    } else {
                        $result[$key] = $value;
                    }
                }
                // Widget's meta data, mostly used for event tracking
                $result['_widgetTitle'] = $variant->searchResultTitle;
                $results[] = $result;
            }
        }

        echo '<table border="1">';
        echo '<tr>';

        foreach ($results as $result) {
            echo '<tr>';
            foreach ($result as $field => $value) {
                echo '<td>' . $field . ': ' . $value . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';

    }
	*/
}
