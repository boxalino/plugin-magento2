<?php
namespace Boxalino\Intelligence\Helper\P13n;
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxSearchRequest;
use com\boxalino\bxclient\v1\BxFilter;

/**
 * Class Adapter
 * @package Boxalino\Intelligence\Helper\P13n
 */
class Adapter
{
	/**
	 * @var null
	 */
    private static $bxClient = null;

	/**
	 * @var string
	 */
	protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

	/**
	 * @var \Magento\Catalog\Model\Category
	 */
	protected $catalogCategory;

	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
    protected $scopeConfig;

	/**
	 * @var \Magento\Framework\App\Request\Http
	 */
    protected $request;

	/**
	 * @var \Magento\Framework\Registry
	 */
    protected $registry;

	/**
	 * @var \Magento\Search\Model\QueryFactory
	 */
    protected $queryFactory;

	/**
	 * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
	 */
    protected $collectionFactory;

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
    protected $storeManager;

	/**
	 * @var \Boxalino\Intelligence\Helper\Data
	 */
	protected $bxHelperData;

	/**
	 * @var
	 */
	protected $currentSearchChoice;

	/**
	 * Adapter constructor.
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Catalog\Model\Category $catalogCategory
	 * @param \Magento\Framework\App\Request\Http $request
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Search\Model\QueryFactory $queryFactory
	 * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
	 * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
	 */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Boxalino\Intelligence\Helper\Data $bxHelperData
    )
    {
		$this->bxHelperData = $bxHelperData;
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
		if($this->bxHelperData->isPluginEnabled()){
			$this->initializeBXClient();

		}
    }

	/**
	 * Initializes the \com\boxalino\bxclient\v1\BxClient
	 */
	protected function initializeBXClient() {

		if(self::$bxClient == null) {
			
			$account = $this->scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
			$password = $this->scopeConfig->getValue('bxGeneral/general/password',$this->scopeStore);
			$isDev = $this->scopeConfig->getValue('bxGeneral/general/dev',$this->scopeStore);
			$host = $this->scopeConfig->getValue('bxGeneral/advanced/host',$this->scopeStore);
			$p13n_username = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_username',$this->scopeStore);
			$p13n_password = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_password',$this->scopeStore);
			$domain = $this->scopeConfig->getValue('bxGeneral/general/domain',$this->scopeStore);
			self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
		}
	}

	/**
	 * @param string $queryText
	 * @return array
	 */
	public function getSystemFilters($queryText="") {
		
		$filters = array();
		if($queryText == "") {
			$filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_visibility_' . $this->bxHelperData->getLanguage(), array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH), true);
		} else {
			$filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_visibility_' . $this->bxHelperData->getLanguage(), array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG), true);
		}
		$filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_status', array(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));

		return $filters;
	}

	/**
	 * @return mixed|string
	 */
	public function getAutocompleteChoice() {
		
		$choice = $this->scopeConfig->getValue('bxSearch/advanced/autocomplete_choice_id',$this->scopeStore);
		if($choice == null) {
			$choice = "autocomplete";
		}
		return $choice;
	}

	/**
	 * @param $queryText
	 * @return mixed|string
	 */
	public function getSearchChoice($queryText) {
		
		if($queryText == null) {
			$choice = $this->scopeConfig->getValue('bxSearch/advanced/navigation_choice_id',$this->scopeStore);
			if($choice == null) {
				$choice = "navigation";
			}
			$this->currentSearchChoice = $choice;
			return $choice;
		}
		
		$choice = $this->scopeConfig->getValue('bxSearch/advanced/search_choice_id',$this->scopeStore);
		if($choice == null) {
			$choice = "search";
		}
		$this->currentSearchChoice = $choice;
		return $choice;
	}

	/**
	 * @return mixed|string
	 */
	public function getEntityIdFieldName() {
		
		$entityIdFieldName = $this->scopeConfig->getValue('bxGeneral/advanced/entity_id',$this->scopeStore);
		if (!isset($entity_id) || $entity_id === '') {
			$entityIdFieldName = 'id';
		}
		return $entityIdFieldName;
	}

	/**
	 * @param string $queryText
	 * @param \Boxalino\Intelligence\Helper\Autocomplete $autocomplete
	 * @return array
	 */
	public function autocomplete($queryText, \Boxalino\Intelligence\Helper\Autocomplete $autocomplete) {
		
		$order = array();
		$data = array();
		$hash = null;
		
		$autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit',$this->scopeStore);
		$products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit',$this->scopeStore);
			
		if ($queryText) {
			
			$bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->bxHelperData->getLanguage(), $queryText, $autocomplete_limit, $products_limit, $this->getAutocompleteChoice(), $this->getSearchChoice($queryText));
			$searchRequest = $bxRequest->getBxSearchRequest();

			$searchRequest->setReturnFields(array('products_group_id'));
			$searchRequest->setGroupBy('products_group_id');
			$searchRequest->setFilters($this->getSystemFilters($queryText));
			self::$bxClient->setAutocompleteRequest($bxRequest);
			self::$bxClient->autocomplete();
			$bxAutocompleteResponse = self::$bxClient->getAutocompleteResponse();

			$entity_ids = array();
			foreach($bxAutocompleteResponse->getBxSearchResponse()->getHitIds($this->currentSearchChoice) as $id) {
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
				
				foreach($bxAutocompleteResponse->getBxSearchResponse($suggestion)->getHitIds($this->currentSearchChoice) as $id) {
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
			foreach($bxAutocompleteResponse->getBxSearchResponse()->getHitIds($this->currentSearchChoice) as $id) {
				$row = array();
				$row['type'] = 'global_products';
				$row['row_class'] = 'suggestion-item global_product_suggestions';
				$row['product'] = $productValues[$id];
				$row['first'] = $first;
				$first = false;
				$data[] = $row;
			}
			
			foreach ($bxAutocompleteResponse->getTextualSuggestions() as $i => $suggestion) {
				foreach($bxAutocompleteResponse->getBxSearchResponse($suggestion)->getHitIds($this->currentSearchChoice) as $id) {
					$data[] = array("type"=>"sub_products","product"=> $productValues[$id], 'row_class'=>'suggestion-item sub_product_suggestions sub_id_' . $i);
				}
			}
		}
		return $data;
	}

	/**
	 * @param $queryText
	 * @param int $pageOffset
	 * @param null $overwriteHitcount
	 * @param \com\boxalino\bxclient\v1\BxSortFields|null $bxSortFields
	 * @param null $categoryId
	 */
    public function search($queryText, $pageOffset = 0, $overwriteHitcount = null, \com\boxalino\bxclient\v1\BxSortFields $bxSortFields=null, $categoryId=null){
		
		$returnFields = array('products_group_id'/*$this->getEntityIdFieldName()*/, 'categories', 'discountedPrice', 'title', 'score');
		$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields',$this->scopeStore));
		$returnFields = array_merge($returnFields, $additionalFields);

		$hitCount = isset($_REQUEST['product_list_limit'])? $_REQUEST['product_list_limit'] : $this->getMagentoStoreConfigPageSize();

		//create search request
		$bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($this->bxHelperData->getLanguage(), $queryText, $hitCount, $this->getSearchChoice($queryText));
		$bxRequest->setReturnFields($returnFields);
		$bxRequest->setOffset($pageOffset);
		$bxRequest->setSortFields($bxSortFields);
		$bxRequest->setFacets($this->prepareFacets());
		$bxRequest->setFilters($this->getSystemFilters($queryText));

		if($categoryId != null) {
			$filterField = "category_id";
			$filterValues = array($categoryId);
			$filterNegative = false;
			$bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
		}

		self::$bxClient->addRequest($bxRequest);
    }

	/**
	 * @return mixed
	 */
	public function getMagentoStoreConfigPageSize() {
		
		$storeConfig = $this->scopeConfig->getValue('catalog/frontend');
		$storeDisplayMode = $storeConfig['list_mode'];

		//we may get grid-list, list-grid, grid or list
		$storeMainMode = explode('-', $storeDisplayMode);
		$storeMainMode = $storeMainMode[0];
		$hitCount = $storeConfig[$storeMainMode . '_per_page'];
		return $hitCount;
	}

	/**
	 * 
	 */
	public function simpleSearch() {

		$query = $this->queryFactory->get();
		$queryText = $query->getQueryText();
		if(self::$bxClient->getChoiceIdRecommendationRequest($this->getSearchChoice($queryText))!=null) {
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
//				$categoryId = $category->getId();
			}
			// GET param 'cat' may override the current_category,
			// i.e. when clicking on subcategories in a category page
			$cat = $this->request->getParam('cat');
			if (!empty($cat)) {
				$_REQUEST[$this->getUrlParameterPrefix() . 'category_id'][0] = $cat;
			}
		}
		
		$overWriteLimit = isset($_REQUEST['product_list_limit'])? $_REQUEST['product_list_limit'] : $this->getMagentoStoreConfigPageSize();
		$pageOffset = isset($_REQUEST['p'])? ($_REQUEST['p']-1)*($overWriteLimit) : 0;
		$this->search($queryText, $pageOffset, $overWriteLimit, new \com\boxalino\bxclient\v1\BxSortFields($field, $dir), $categoryId);
	}
	
	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getLeftFacetFieldNames() {
		
		return array_keys($this->bxHelperData->getLeftFacets());
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getAllFacetFieldNames() {
		
		$allFacets = array_keys($this->bxHelperData->getLeftFacets());
		if($this->getTopFacetFieldName() != null) {
			$allFacets[] = $this->getTopFacetFieldName();
		}
		return $allFacets;
	}

	/**
	 * @return string
	 */
	private function getUrlParameterPrefix() {
		
		return 'bx_';
	}

	/**
	 * @return \com\boxalino\bxclient\v1\BxFacets|null
	 * @throws \Exception
	 */
    private function prepareFacets(){
		
		if($this->bxHelperData->isSearchEnabled()){
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
			foreach($this->bxHelperData->getLeftFacets() as $fieldName => $facetValues) {
				$selectedValue = isset($selectedValues[$fieldName][0]) ? $selectedValues[$fieldName][0] : null;
				$bxFacets->addFacet($fieldName, $selectedValue, $facetValues[1], $facetValues[0], $facetValues[2]);
			}


			list($topField, $topOrder) = $this->bxHelperData->getTopFacetValues();
			if($topField) {
				$selectedValue = isset($selectedValues[$topField][0]) ? $selectedValues[$topField][0] : null;
				$bxFacets->addFacet($topField, $selectedValue, "string", $topField, $topOrder); // 1 ?? *iku*
			}

			return $bxFacets;
		}
		return null;
    }

	/**
	 * @return mixed
	 */
	public function getTopFacetFieldName() {
		
		list($topField, $topOrder) = $this->bxHelperData->getTopFacetValues();
		return $topField;
	}

	/**
	 * @return mixed
	 */
    public function getTotalHitCount(){
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getTotalHitCount($this->currentSearchChoice);
    }

	/**
	 * @return mixed
	 */
    public function getEntitiesIds(){
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getHitIds($this->currentSearchChoice);
    }

	/**
	 * @return null
	 */
	public function getFacets() {
		
		$this->simpleSearch();
		$facets = self::$bxClient->getResponse()->getFacets($this->currentSearchChoice);
		if(empty($facets)){
			return null;
		}
		
		$facets->setParameterPrefix($this->getUrlParameterPrefix());
		return $facets;
	}

	/**
	 * @return mixed
	 */
	public function getCorrectedQuery() {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getCorrectedQuery($this->currentSearchChoice);
	}

	/**
	 * @return mixed
	 */
	public function areResultsCorrected() {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->areResultsCorrected($this->currentSearchChoice);
	}

	/**
	 * @return mixed
	 */
	public function areThereSubPhrases() {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->areThereSubPhrases($this->currentSearchChoice);
	}

	/**
	 * @return mixed
	 */
	public function getSubPhrasesQueries() {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhrasesQueries($this->currentSearchChoice);
	}

	/**
	 * @param $queryText
	 * @return mixed
	 */
	public function getSubPhraseTotalHitCount($queryText) {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhraseTotalHitCount($queryText);
	}

	/**
	 * @param $queryText
	 * @return mixed
	 */
	public function getSubPhraseEntitiesIds($queryText) {
		
		$this->simpleSearch();
		return self::$bxClient->getResponse()->getSubPhraseHitIds($queryText, $this->getEntityIdFieldName());
	}

	/**
	 * @param $widgetName
	 * @param string $widgetType
	 * @param int $minAmount
	 * @param int $amount
	 * @param array $context
	 * @param bool $execute
	 * @return array
	 */
    public function getRecommendation($widgetName, $widgetType = '', $minAmount = 3, $amount = 3, $context = array(), $execute=true){
		
		if(!$execute) {
			if ($widgetType == '') {
				$bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->bxHelperData->getLanguage(), $widgetName, $amount);
				$bxRequest->setMin($minAmount);
				$bxRequest->setFilters($this->getSystemFilters());
				if (isset($context[0])) {
					$product = $context[0];
					$bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
				}
				self::$bxClient->addRequest($bxRequest);
			} else {
				if (($minAmount >= 0) && ($amount >= 0) && ($minAmount <= $amount)) {
					$bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->bxHelperData->getLanguage(), $widgetName, $amount, $minAmount);
					$bxRequest->setFilters($this->getSystemFilters());
					if ($widgetType === 'basket' && is_array($context)) {
						$basketProducts = array();
						foreach($context as $product) {
							$basketProducts[] = array('id'=>$product->getId(), 'price'=>$product->getPrice());
						}
						$bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
					} elseif ($widgetType === 'product' && $context != null) {
						$product = $context;
						$bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
					} elseif ($widgetType === 'category' && $context != null){
						$filterField = "category_id";
						
						if (!is_array($context)) {
							$filterValues = array($context);
						} else {
							$filterValues = $context;
						}
						$filterNegative = false;
						$bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
					}
					self::$bxClient->addRequest($bxRequest);
				}
			}
			return array();
		}
		return self::$bxClient->getResponse()->getHitIds($widgetName);
    }
}
