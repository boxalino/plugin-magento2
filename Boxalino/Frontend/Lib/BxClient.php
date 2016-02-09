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
	
	private $filters = array();
	private $autocompleteResponse = null;
	private $choiceResponse = null;
	
    const VISITOR_COOKIE_TIME = 31536000;

	public function __construct($account, $isDev, $host, $p13n_username, $p13n_password, $domain, $language, $additionalFields, $p13n) {
		$this->account = $account;
		$this->isDev = $isDev;
		$this->host = $host;
		$this->p13n_username = $p13n_username;
		$this->p13n_password = $p13n_password;
		$this->domain = $domain;
		$this->language = $language;
		$this->additionalFields = $additionalFields;
		$this->p13n = $p13n;
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
		$this->choiceResponse = $p13n->choose($choiceRequest);

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
	
	private $recommendationResponses = array();
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
		
		$this->recommendationResponses = $this->choose($choiceRequest);
	}
	
	public function getChoiceRecommendations($choiceId, $bxRecommendations, $returnFields = array()) {
		if(!isset($this->recommendationResponses[$choiceId])) {
			$this->recommend($bxRecommendations, $returnFields);
		}
		
		if(!isset($this->recommendationResponses[$choiceId])) {
			throw new \Exception("Problem to create recommendations for choice " . $choic);
		}
		
		foreach ($choiceResponse->variants as $variantId => $variant) {
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
	
	public function getChoiceResponse($func="getChoiceResponse") {
		if($this->choiceResponse == null) {
			throw new \Exception("$func called before any call to autocomplete method");
		}
		return $this->choiceResponse;
	}
	
	public function getAdditionalData($fields) {
		$result = array();
        $response = $this->getChoiceResponse("getAdditionalData");
        if (!empty($response->variants)) {
            foreach ($response->variants as $variant) {
                /** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
                $searchResult = $variant->searchResult;
                foreach ($searchResult->hits as $item) {
                    foreach ($fields as $field) {
                        if (isset($item->values[$field])) {
                            if (!empty($item->values[$field])) {
                                $result[$item->values['id'][0]][$field] = $item->values[$field];
                            }
                        }
                    }
                }
            }
        }
		return $result;
    }
	
    public function autocomplete($queryText, $suggestionsHitCount, $hitCount = 0, $returnFields = array(), $acExtraEnabled = true, $autocompleteChoice = 'autocomplete', $searchChoice = 'search')
    {
        $searchQuery = $this->getSimpleSearchQuery($returnFields, $hitCount, $queryText);
		if ($acExtraEnabled) {
            $searchQuery->facetRequests[] = $this->getCategoryFacet();
        }
		
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

    protected function getFacetLeafs($facets, $hit, $config)
    {
        $tmp = array();

        foreach ($facets as $facet) {

            foreach ($facet->hierarchy as $h) {
                if (array_key_exists($h, $tmp)) {
                    unset($tmp[$h]);
                }
            }

            $tmp[end($facet->hierarchy)] = array(
                'title' => end($facet->hierarchy),
                'hits' => $facet->hitCount,
                'href' => $facet->stringValue,
                'id' => substr(md5($hit->suggestion . '_' . $facet->stringValue), 0, 10)
            );
        }

        if ($config['sort']) {
            usort($tmp, array($this, 'cmpFacets'));
        }

        return $tmp;
    }

    public function getAutocompleteEntities($acItems = true, $acExtraEnabled = true)
    {
		$suggestions = array();
		
		foreach ($this->getAutocompleteResponse()->hits as $hit) {

            $tmp = array('text' => $hit->suggestion, 'html' => (strlen($hit->highlighted) ? $hit->highlighted : $hit->suggestion), 'hits' => $hit->searchResult->totalHitCount);
            $facets = array();

            if ($acExtraEnabled) {
                $tmp['facets'] = array_slice($this->getFacetLeafs($hit->searchResult->facetResponses[0]->values, $hit, $config),
                    0, $acItems);
            }

            $suggestions[] = $tmp;
        }
        return $suggestions;
    }

    public function getAutocompleteProducts($facets, $map = null, $fields = null, $entity_id = 'id', $searchChoice='search')
    {
		if (!is_array($facets)) {
            $facets = array();
        }

        $fs = array();
        foreach($facets as $f) {
            $fs[] = $f['id'];
        }

        if (!is_array($map)) {
            $map = array($entity_id => $entity_id);
        }

        if (!is_array($fields)) {
            $fields = array($entity_id);
        }

        // prefix search result
        $products = array();
        $id = substr(md5($this->getAutocompleteResponse()->prefixSearchResult->queryText), 0, 10);
        $products[$id] = $this->extractItemsFromHits($this->getAutocompleteResponse()->prefixSearchResult->hits, $id, $entity_id, $map);

        $lang = $this->language;
        $i = 0;
        foreach ($this->getAutocompleteResponse()->hits as $hit) {
            if ($i++ >= $autocomplete_limit) {
                break;
            }

            $id = substr(md5($hit->suggestion), 0, 10);
            $products[$id] = $this->extractItemsFromHits($hit->searchResult->hits, $id, $entity_id, $map);
        }

        return $products;
    }

    protected function getFacetDepth($facet)
    {

        return substr_count($facet->stringValue, '/');

    }

    private function cmpFacets($a, $b)
    {
        if ($a['hits'] == $b['hits']) {
            return 0;
        }
        return ($a['hits'] > $b['hits']) ? -1 : 1;
    }

    private function extractItemsFromHits($hits, $hash, $entity_id, $map)
    {
        $items = array();
        foreach ($hits as $item) {
            $tmp = array();
            $tmp['hash'] = $hash;
            foreach ($item->values as $key => $value) {
                if (array_key_exists($key, $map)) {
                    if (is_array($value)) {
                        $tmp[$map[$key]] = array_shift($value);
                    } else {
                        $tmp[$map[$key]] = $value;
                    }
                }
            }
            $items[] = $tmp;
        }
        return $items;
    }

    public function getTotalHitCount()
    {
		$count = 0;
        $response = $this->getChoiceResponse("getTotalHitCount");
        if(isset($response->variants)) {
			foreach ($response->variants as $variant) {
				$count += $variant->searchResult->totalHitCount;
			}
		}
        return $count;
    }

    public function getEntitiesIds($entityIdFieldName)
    {
		$result = array();
        $response = $this->getChoiceResponse("getEntitiesIds");
        if(isset($response->variants)) {
			foreach ($response->variants as $variant) {
				/** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
				$searchResult = $variant->searchResult;
				foreach ($searchResult->hits as $item) {
					$result[] = $item->values[$entityIdFieldName][0];
				}
			}
		}

        return $result;
    }

    public function getFacetsData()
    {
        $preparedFacets = array();
        $response = $this->getChoiceResponse("getFacetsData");
		if(isset($response->variants)) {
			foreach ($response->variants as $variant) {
				$facets = $variant->searchResult->facetResponses;
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
			}
		}
        return $preparedFacets;
    }

    public function getChoiceRelaxation()
    {
        $response = $this->getChoiceResponse("getChoiceRelaxation");
		if(isset($response->variants[0]->searchRelaxation)) {
			return $response->variants[0]->searchRelaxation;
		}
		return null;
    }

    public function printData()
    {
        $results = array();
        $response = $this->getChoiceResponse("printData");
        /** @var \com\boxalino\p13n\api\thrift\Variant $variant */
        foreach ($response->variants as $variant) {
            /** @var \com\boxalino\p13n\api\thrift\SearchResult $searchResult */
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
}
