<?php
namespace Boxalino\Intelligence\Helper\P13n;
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\bxclient\v1\BxFilter;
class Adapter
{
    private static $bxClient = null;
    
	protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
	protected $catalogCategory;
    protected $scopeConfig;
    protected $request;
    protected $registry;
    protected $queryFactory;
    protected $collectionFactory;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Boxalino\Intelligence\Helper\Data $bxHelperData
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
		$this->request = $request;
		$this->registry = $registry;
		$this->queryFactory = $queryFactory;
		$this->collectionFactory = $collectionFactory;
		$this->storeManager = $storeManager;
		
	   $libPath = __DIR__ . '/../../Lib';
		require_once($libPath . '/BxClient.php');
		\com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
		
        $this->initializeBXClient();

    }
	
	protected function initializeBXClient() {
		
		if(self::$bxClient == null) {
			
			$account = $this->scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
			$password = $this->scopeConfig->getValue('bxGeneral/general/password',$this->scopeStore);
			$isDev = $this->scopeConfig->getValue('bxGeneral/general/dev',$this->scopeStore);
			$host = $this->scopeConfig->getValue('bxGeneral/advanced/host',$this->scopeStore);
			$p13n_username = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_username',$this->scopeStore);
			$p13n_password = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_password',$this->scopeStore);
			$domain = $this->scopeConfig->getValue('bxGeneral/general/domain',$this->scopeStore);
			//$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields',$this->scopeStore));
			//$language = substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);
			
			self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
			
		}
	}
	
	public function getSystemFilters() {
		
		$filters = array();
		$filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_visibility', array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG), true);
		$filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_status', array(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
		return $filters;
	}
	
	public function resetSearchAdapter() {
		self::$bxClient = null;
		$this->initializeBXClient();
	}
	
	public function getAutocompleteChoice() {
		
		$choice = $this->scopeConfig->getValue('bxSearch/advanced/autocomplete_choice_id',$this->scopeStore);
		if($choice == null) {
			$choice = "autocomplete";
		}
		return $choice;
	}
	
	public function getSearchChoice() {
		
		$choice = $this->scopeConfig->getValue('bxSearch/advanced/search_choice_id',$this->scopeStore);
		if($choice == null) {
			$choice = "search";
		}
		return $choice;
	}
	
	public function getEntityIdFieldName() {
		$entityIdFieldName = $this->scopeConfig->getValue('bxGeneral/advanced/entity_id',$this->scopeStore);
		if (!isset($entity_id) || $entity_id === '') {
			$entityIdFieldName = 'id';
		}
		return $entityIdFieldName;
	}
	
	public function isEnabled() {
		$enabled = $this->scopeConfig->getValue('bxGeneral/general/enabled',$this->scopeStore);
		return false;
	}
	
	public function getLanguage() {
		return substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);;
	}
	
	public function autocomplete($queryText, $autocomplete) {
		$order = array();
		$hash = null;
		
		$data = array();
		
		$autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit',$this->scopeStore);
		$products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit',$this->scopeStore);
			
		if ($queryText) {
			
			$bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->getLanguage(), $queryText, $autocomplete_limit, $products_limit, $this->getAutocompleteChoice(), $this->getSearchChoice());
			self::$bxClient->setAutocompleteRequest($bxRequest);
			self::$bxClient->autocomplete($queryText, $autocomplete_limit, $products_limit, $this->getAutocompleteChoice(), $this->getSearchChoice());
			
			$bxAutocompleteResponse = self::$bxClient->getAutocompleteResponse();
			
			$entity_ids = array();
			foreach($bxAutocompleteResponse->getBxSearchResponse()->getHitIds() as $id) {
				$entity_ids[$id] = $id;
			}
			
			foreach ($bxAutocompleteResponse->getTextualSuggestions() as $i => $suggestion) {
				
				$totalHitcount = $bxAutocompleteResponse->getTextualSuggestionTotalHitCount($suggestion);
				
                if ($totalHitcount <= 0) {
                    continue;
                }
				
				$_data = array(
                    'title' => $suggestion,
                    'num_results' => $totalHitcount,
                    'type' => 'suggestion',
					'id' => $i,
					'row_class' => 'acsuggestions'
                );
				
				foreach($bxAutocompleteResponse->getBxSearchResponse($suggestion)->getHitIds() as $id) {
					$entity_ids[$id] = $id;
				}

				if ($_data['title'] == $queryText) {
					array_unshift($data, $_data);
				} else {
					$data[] = $_data;
				}
            }
		}

		if(sizeof($entity_ids) > 0) {

			$list = $this->collectionFactory->create()->setStoreId($this->storeManager->getStore()->getId())
				->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');
			$list->load();

			$productValues = $autocomplete->getListValues($list);
			
			$first = true;
			foreach($bxAutocompleteResponse->getBxSearchResponse()->getHitIds() as $id) {
				$row = array();
				$row['type'] = 'global_products';
				$row['row_class'] = 'suggestion-item global_product_suggestions';
				$row['product'] = $productValues[$id];
				$row['first'] = $first;
				$first = false;
				$data[] = $row;
			}
			
			foreach ($bxAutocompleteResponse->getTextualSuggestions() as $i => $suggestion) {
				foreach($bxAutocompleteResponse->getBxSearchResponse($suggestion)->getHitIds() as $id) {
					$data[] = array("type"=>"sub_products","product"=> $productValues[$id], 'row_class'=>'suggestion-item sub_product_suggestions sub_id_' . $i);
				}
			}
		}

		return $data;
	}

    public function search($queryText, $pageOffset = 0, $overwriteHitcount = null, $bxSortFields=null)
    {
		$returnFields = array($this->getEntityIdFieldName(), 'categories', 'discountedPrice', 'title', 'score');
		$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields',$this->scopeStore));
		$returnFields = array_merge($returnFields, $additionalFields);
		
		$hitCount = $overwriteHitcount != null ? $overwriteHitcount : $this->scopeConfig->getValue('bxSearch/search/limit',$this->scopeStore);
		if($hitCount == null) {
			$hitCount = $this->getMagentoStoreConfigPageSize();
		}
		
		$offset = $pageOffset * $hitCount;
		
		//create search request
		$bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($this->getLanguage(), $queryText, $hitCount, $this->getSearchChoice());
		$bxRequest->setReturnFields($returnFields);
		$bxRequest->setOffset($offset);
		$bxRequest->setSortFields($bxSortFields);
		$bxRequest->setFacets($this->prepareFacets());
		
		//add the request
		self::$bxClient->addRequest($bxRequest);
    }
	
	public function getMagentoStoreConfigPageSize() {
		$storeConfig = $this->scopeConfig->getValue('catalog/frontend');
			
		$storeDisplayMode = $storeConfig['list_mode'];

		//we may get grid-list, list-grid, grid or list

		$storeMainMode = explode('-', $storeDisplayMode);

		$storeMainMode = $storeMainMode[0];

		$hitCount = $storeConfig[$storeMainMode . '_per_page'];
		
		return $hitCount;
	}
	
	public function simpleSearch() {
		
		if(self::$bxClient->getRequest()!=null) {
			return;
		}
		
		$field = '';
		$dir = '';
		$order = $this->request->getParam('product_list_order');

		if(isset($order)){
			if($order == 'name'){
				$field = 'title';
			} elseif($order == 'price'){
				$field = 'discountedPrice';
			}
		}
		$dirOrder = $this->request->getParam('product_list_dir');
		if($dirOrder){
			$dir = $dirOrder == 'asc' ? false : true;
		} else{
			$dir = true;
		}

		$categoryId = $this->request->getParam($this->getUrlParameterPrefix() . 'category_id');
		if (empty($categoryId)) {
			/* @var $category Mage_Catalog_Model_Category */
			$category = $this->registry->registry('current_category');
			if (!empty($category)) {
				$_REQUEST[$this->getUrlParameterPrefix() . 'category_id'][0] = $category->getId();
			}
			// GET param 'cat' may override the current_category,
			// i.e. when clicking on subcategories in a category page
			$cat = $this->request->getParam('cat');
			if (!empty($cat)) {
				$_REQUEST[$this->getUrlParameterPrefix() . 'category_id'][0] = $cat;
			}
		}

		$overWriteLimit = (int) $this->request->getParam('limit',null);
		$pageOffset = abs(((int) $this->request->getParam('p', 1)) - 1);

		$query = $this->queryFactory->get();
		$this->search($query->getQueryText(), $pageOffset, $overWriteLimit, new \com\boxalino\bxclient\v1\BxSortFields($field, $dir));
            
	}
	
	private function getLeftFacets() {
		$fields = explode(',', $this->scopeConfig->getValue('bxSearch/left_facets/fields',$this->scopeStore));
		$labels = explode(',', $this->scopeConfig->getValue('bxSearch/left_facets/labels',$this->scopeStore));
		$types = explode(',', $this->scopeConfig->getValue('bxSearch/left_facets/types',$this->scopeStore));
		$orders = explode(',', $this->scopeConfig->getValue('bxSearch/left_facets/orders',$this->scopeStore));
		
		if($fields[0] == "") {
			return array();
		}
		
		if(sizeof($fields) != sizeof($labels)) {
			throw new \Exception("number of defined left facets fields doesn't match the number of defined left facet labels: " . implode(',', $fields) . " versus " . implode(',', $labels));
		}
		if(sizeof($fields) != sizeof($types)) {
			throw new \Exception("number of defined left facets fields doesn't match the number of defined left facet types: " . implode(',', $fields) . " versus " . implode(',', $types));
		}
		if(sizeof($fields) != sizeof($orders)) {
			throw new \Exception("number of defined left facets fields doesn't match the number of defined left facet orders: " . implode(',', $fields) . " versus " . implode(',', $orders));
		}
		
		$facets = array();
		foreach($fields as $k => $field){
			$facets[$field] = array($labels[$k], $types[$k], $orders[$k]);
		}
		
		return $facets;
	}
	
	private function getTopFacetValues() {
		$field = $this->scopeConfig->getValue('bxSearch/top_facet/field',$this->scopeStore);
		$order = $this->scopeConfig->getValue('bxSearch/top_facet/order',$this->scopeStore);
		return array($field, $order);
	}
	
	public function getLeftFacetFieldNames() {
		return array_keys($this->getLeftFacets());
	}
	
	public function getAllFacetFieldNames() {
		$allFacets = array_keys($this->getLeftFacets());
		if($this->getTopFacetFieldName() != null) {
			$allFacets[] = $this->getTopFacetFieldName();
		}
		return $allFacets;
	}
	
	private function getUrlParameterPrefix() {
		return 'bx_';
	}

	public function getCategoryEntitiesIds($id){

		$language = "en";
		$queryText = "";
		$hitCount = 20;
		$filterField = "category_id";
		$filterValues = array($id);
		$filterNegative = true;

		$bxRequest = new BxSearchRequest($language, $queryText, $hitCount);
		$bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
		self::$bxClient->addRequest($bxRequest);
		$bxResponse = self::$bxClient->getResponse();
		return $bxResponse->getHitIds();
	}

    private function prepareFacets()
    {
		$bxFacets = new \com\boxalino\bxclient\v1\BxFacets();
		
		$selectedValues = array();
		foreach ($_REQUEST as $key => $values) {
			if (strpos($key, $this->getUrlParameterPrefix()) !== false) {
				$fieldName = substr($key, 3);
				$selectedValues[$fieldName] = !is_array($values)?array($values):$values;
			}
        }
		
		$catId = isset($selectedValues['category_id']) && sizeof($selectedValues['category_id']) > 0 ? $selectedValues['category_id'][0] : null;
		$bxFacets->addCategoryFacet($catId);
        
		foreach($this->getLeftFacets() as $fieldName => $facetValues) {
			$selectedValue = isset($selectedValues[$fieldName][0]) ? $selectedValues[$fieldName][0] : null;
			$bxFacets->addFacet($fieldName, $selectedValue, $facetValues[1], $facetValues[0], $facetValues[2]);
		}
		
		list($topField, $topOrder) = $this->getTopFacetValues();
		if($topField) {
			$selectedValue = isset($selectedValues[$topField][0]) ? $selectedValues[$topField][0] : null;
			$bxFacets->addFacet($topField, $selectedValue, "string", $topField, $topOrder); // 1 ?? *iku*
		}
		
        return $bxFacets;
    }
	
	public function getTopFacetFieldName() {
		list($topField, $topOrder) = $this->getTopFacetValues();
		return $topField;
	}

    public function getTotalHitCount()
    {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getTotalHitCount();
    }

    public function getEntitiesIds()
    {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getHitIds();
    }

	public function getFacets() {
		$this->simpleSearch();
		$facets = self::$bxClient->getResponse()->getFacets();
		$facets->setParameterPrefix($this->getUrlParameterPrefix());
		return $facets;
	}
	
	public function getCorrectedQuery() {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getCorrectedQuery();
	}
	
	public function areResultsCorrected() {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->areResultsCorrected();
	}
	
	public function areThereSubPhrases() {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->areThereSubPhrases();
	}
	
	public function getSubPhrasesQueries() {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhrasesQueries();
	}
	
	public function getSubPhraseTotalHitCount($queryText) {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhraseTotalHitCount($queryText);
	}
	
	public function getSubPhraseEntitiesIds($queryText) {
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhraseEntitiesIds($queryText, $this->getEntityIdFieldName());
	}

    public function getRecommendation($widgetType, $widgetName, $minAmount = 3, $amount = 3, $products = array())
    {
		if(self::$bxClient->getRequest()==null) {
			$recommendations = $this->scopeConfig->getValue('bxRecommendations',$this->scopeStore);
			if ($widgetType == '') {
				
				$bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->getLanguage(), $widgetName, $amount);
				$bxRequest->setMin($minAmount);
				
				if (isset($products[0])) {
					$product = $products[0];
					$bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
				}
				self::$bxClient->addRequest($bxRequest);
			} else {
				foreach ($recommendations as $key => $recommendation) {

					$type = 'others';
					if($key == 'cart') {
						$type = 'basket';
					}
					if($key == 'related' || $key == 'upsell') {
						$type = 'product';
					}

					if (
						(!empty($recommendation['min']) || $recommendation['min'] >= 0) &&
						(!empty($recommendation['max']) || $recommendation['max'] >= 0) &&
						($recommendation['min'] <= $recommendation['max']) &&
						(!isset($recommendation['enabled']) || $recommendation['enabled'] == 1)
					) {

						if ($type == $widgetType) {

							$bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->getLanguage(), $recommendation['widget'], $recommendation['max']);
							$bxRequest->setMin($recommendation['min']);

							if ($widgetType === 'basket') {
							;
								$basketProducts = array();
								foreach($products as $product) {
									$basketProducts[] = array('id'=>$product->getid(), 'price'=>$product->getPrice());
								}
								$bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
							} elseif ($widgetType === 'product' && isset($products[0])) {

								$product = $products[0];
								$bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
							}
							self::$bxClient->addRequest($bxRequest);
						}
					}
				}
			}
		}
		
		return self::$bxClient->getResponse()->getHitIds($widgetName);
    }

}
