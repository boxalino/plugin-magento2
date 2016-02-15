<?php
namespace Boxalino\Frontend\Helper\P13n;

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
        \Boxalino\Frontend\Helper\Data $bxHelperData
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
		$this->request = $request;
		$this->registry = $registry;
		$this->queryFactory = $queryFactory;
		$this->collectionFactory = $collectionFactory;
		$this->storeManager = $storeManager;
		
        $this->initializeBXClient();

    }
	
	protected function initializeBXClient() {
		
		if(self::$bxClient == null) {
			
			//initialize class of HttpP13n (required to have client available)
			new \Boxalino\Frontend\Lib\vendor\Thrift\HttpP13n();
			
			$account = $this->scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
			$isDev = $this->scopeConfig->getValue('bxGeneral/general/dev',$this->scopeStore);
			$host = $this->scopeConfig->getValue('bxGeneral/advanced/host',$this->scopeStore);
			$p13n_username = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_username',$this->scopeStore);
			$p13n_password = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_password',$this->scopeStore);
			$domain = $this->scopeConfig->getValue('bxGeneral/general/domain',$this->scopeStore);
			$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields',$this->scopeStore));
			
			$language = substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);
			
			self::$bxClient = new \BxClient($account, $isDev, $host, $p13n_username, $p13n_password, $domain, $language, $additionalFields);
			self::$bxClient->addBxFilter(new \BxFilter('products_visibility', array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG), true));
			self::$bxClient->addBxFilter(new \BxFilter('products_status', array(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)));
			
		}
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

    public function __destruct()
    {
        
    }
	
	public function addFilterFromTo($field, $from, $to, $localized = false)
    {
		self::$bxClient->addFilterFromTo($field, $from, $to, $localized);
	}
	
	public function addFilter($field, $value, $localized = false, $prefix = 'products_', $bodyName = 'description')
    {
		self::$bxClient->addFilter($field, $value, $localized, $prefix, $bodyName);
	}

    /**
     * @param int $hierarchyId how deep is category tree in search, starts from 0 for main categories
     * @param array $category names of categories in hierarchy
     *
     * exaples:
     * $hierarchyId = 0;
     * $category = array('Men');
     * will search all products in category 'Men' (with subcategories)
     *
     * $hierarchyId = 1;
     * $category = array('Men', 'Blazers');
     * will search all products in category 'Men' (with subcategories)
     *
     */
    public function addFilterCategories($categoryId)
    {
        $categoryNames = array();
        if (isset($categoryId) && $categoryId > 0) {
            $category = $this->catalogCategory->load($categoryId);
            $path = $category->getPath();
            $pathArray = explode('/', $path);
            $skip = -2;
            foreach ($pathArray as $catId) {
                $categoryName = $this->catalogCategory->load($catId)->getName();

                if (++$skip > 0) {
                    $categoryNames[] = $categoryName;
                }
            }

            self::$bxClient->addFilterHierarchy('categories', $categoryId, $categoryNames);

        }
    }

    /**
     * @param $categoryId
     */
    public function addFilterCategory($categoryId)
    {
        if (isset($categoryId) && $categoryId > 0) {
            $category = $this->catalogCategory->load($categoryId);
			if ($category != null) {
				self::$bxClient->addFilterCategory($categoryId, $category->getName());
            }
        }
    }

    /**
     * @param float $from
     * @param float $to
     */
    public function setupPrice($from, $to)
    {
		self::$bxClient->addFilterFromTo('discountedPrice', $from, $to);
    }
	
	public function getEntityIdFieldName() {
		$entityIdFieldName = $this->scopeConfig->getValue('bxGeneral/advanced/entity_id',$this->scopeStore);
		if (!isset($entity_id) || $entity_id === '') {
			$entityIdFieldName = 'entity_id';
		}
		return $entityIdFieldName;
	}
	
	public function isEnabled() {
		$enabled = $this->scopeConfig->getValue('bxGeneral/general/enabled',$this->scopeStore);
		return false;
	}
	
	public function autocomplete($queryText) {
		$order = array();
		$hash = null;
		
		$data = array();
		
		$autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit',$this->scopeStore);
		$products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit',$this->scopeStore);
			
		if ($queryText) {
			$fields = array($this->getEntityIdFieldName(), 'title', 'score');

			self::$bxClient->autocomplete($queryText, $autocomplete_limit, $products_limit, $fields, $this->getAutocompleteChoice(), $this->getSearchChoice());
			
			$globalProducts = self::$bxClient->getAutocompleteProducts(array($this->getEntityIdFieldName()));
			$entity_ids = $this->mergeProductsInEntityIds(array(), $globalProducts);
            foreach (self::$bxClient->getAutocompleteTextualSuggestions() as $suggestion) {
				
				$totalHitcount = self::$bxClient->getAutocompleteTextualSuggestionTotalHitCount($suggestion);
				
                if ($totalHitcount <= 0) {
                    continue;
                }
				
				$products = self::$bxClient->getAutocompleteProducts(array($this->getEntityIdFieldName()), $suggestion);

				$_data = array(
                    'title' => $suggestion,
                    'num_results' => $totalHitcount,
                    'products' => $products
                );
				
				$entity_ids = $this->mergeProductsInEntityIds($entity_ids, $products);

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
			
			foreach($list as $product) {
				$products[$product->getEntityid()] = $product;
			}
			
			$autocomplete = new \Boxalino\Frontend\Helper\Autocomplete();
			
			$list = $this->getProductsFromIds($globalProducts, $products);
			$globalProductHtml = $autocomplete->getListHtml($list);
			
			$first = true;
			foreach($data as $k => $v) {
				$list = $this->getProductsFromIds($v['products'], $products);
				$productHtml = $autocomplete->getListHtml($list);
				$v['products'] = $productHtml;
				if($first) {
					$v['global_products'] = $globalProductHtml;
				}
				$first = false;
				$data[$k] = $v;
			}
		}
		return $data;
	}
	
	protected function getProductsFromIds($ids, $products) {
		$list = array();
		foreach($ids as $k => $v) {
			$id = $k;
			if(isset($v[$this->getEntityIdFieldName()][0])) {
				$id = $v[$this->getEntityIdFieldName()][0];
			}
			if(isset($products[$id])) {
				$list[] = $products[$id];
			}
		}
		return $list;
	}
	
	protected function mergeProductsInEntityIds($entity_ids, $products) {
		foreach($products as $k => $v) {
			if(isset($v[$this->getEntityIdFieldName()][0])) {
				$entity_ids[$v[$this->getEntityIdFieldName()][0]] = $v[$this->getEntityIdFieldName()][0];
			} else {
				$entity_ids[$k] = $k;
			}
		}
		return $entity_ids;
	}

    public function search($queryText, $pageOffset = 0, $overwriteHitcount = null, $bxSortFields=null)
    {
		$returnFields = array($this->getEntityIdFieldName(), 'categories', 'discountedPrice', 'title', 'score');
		$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields',$this->scopeStore));
		$returnFields = array_merge($returnFields, $additionalFields);
        $searchChoice = $this->getSearchChoice();
		$withRelaxation = true; //$this->scopeConfig->getValue('bxSearch/advanced/relaxation_enabled',$this->scopeStore);
		$bxFacets = $this->prepareFacets();
		
		$hitCount = $overwriteHitcount != null ? $overwriteHitcount : $this->scopeConfig->getValue('bxSearch/search/limit',$this->scopeStore);
		if($hitCount == null) {
			$hitCount = $this->getMagentoStoreConfigPageSize();
		}
		
		$offset = $pageOffset * $hitCount;
		
		self::$bxClient->search($queryText, $hitCount, $returnFields, $searchChoice, $bxFacets, $offset, $bxSortFields, $withRelaxation, $additionalFields);
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
		
		if(self::$bxClient->isSearchDone()) {
			return;
		}
		
		$field = '';
		$dir = '';
		$order = $this->request->getParam('order');
		if(isset($order)){
			if($order == 'name'){
				$field = 'title';
			} elseif($order == 'price'){
				$field = 'discountedPrice';
			}
		}
		$dirOrder = $this->request->getParam('dir');
		if($dirOrder){
			$dir = $dirOrder == 'asc' ? false : true;
		} else{
			$dir = false;
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
		
		$this->search($query->getQueryText(), $pageOffset, $overWriteLimit, new \BxSortFields($field, $dir));
            
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

    private function prepareFacets()
    {
		$requestFacets = array();
		foreach ($_REQUEST as $key => $values) {
			if (strpos($key, $this->getUrlParameterPrefix()) !== false) {
				$fieldName = substr($key, 3);
				$values = !is_array($values)?array($values):$values;
				foreach ($values as $value) {
					$requestFacets[$fieldName][] = $value;
				}
			}
        }
		
		$bxFacets = self::$bxClient->getBxFacets();
		$bxFacets->setParameterPrefix($this->getUrlParameterPrefix());
		
		$bxFacets->setRequestFacets($requestFacets);
		
		if (array_key_exists($this->getUrlParameterPrefix() . 'category_id', $_REQUEST)) {
			$bxFacets->addFacet('category_id', 'cat_id', 'hierarchical', '1');
        }
        
		foreach($this->getLeftFacets() as $fieldName => $facetValues) {
			$bxFacets->addFacet($fieldName, $facetValues[0], $facetValues[1], $facetValues[2]);
		}
		
		list($topField, $topOrder) = $this->getTopFacetValues();
		if($topField) {
			$bxFacets->addFacet($topField, $topField, "string", $topOrder);
		}
		
		self::$bxClient->setBxFacets($bxFacets);
		
        return $bxFacets;
    }
	
	public function getTopFacetFieldName() {
		list($topField, $topOrder) = $this->getTopFacetValues();
		return $topField;
	}

    public function getTotalHitCount()
    {
		$this->simpleSearch();
		return self::$bxClient->getTotalHitCount();
    }

    public function getEntitiesIds()
    {
		$this->simpleSearch();
		return self::$bxClient->getEntitiesIds($this->getEntityIdFieldName());
    }

	public function getCount() {
		return self::$bxClient->getCount();
	}

	public function incrementCount() {
		return self::$bxClient->incrementCount();
	}
	
	/*public function getAdditionalData()
	{
		$this->simpleSearch();
		return self::$bxClient->getAdditionalData();
	}*/

	public function getFacets() {
		$this->simpleSearch();
		return self::$bxClient->getFacets();
	}

    public function getFacetsData()
    {
		$this->simpleSearch();
		return self::$bxClient->getFacetsData();
    }
	
	public function getCorrectedQuery() {
		$this->simpleSearch();
		return self::$bxClient->getCorrectedQuery();
	}
	
	public function areResultsCorrected() {
		$this->simpleSearch();
		return self::$bxClient->areResultsCorrected();
	}
	
	public function areThereSubPhrases() {
		$this->simpleSearch();
		return self::$bxClient->areThereSubPhrases();
	}
	
	public function getSubPhrasesQueries() {
		$this->simpleSearch();
		return self::$bxClient->getSubPhrasesQueries();
	}
	
	public function getSubPhraseTotalHitCount($queryText) {
		$this->simpleSearch();
		return self::$bxClient->getSubPhraseTotalHitCount($queryText);
	}
	
	public function getSubPhraseEntitiesIds($queryText) {
		$this->simpleSearch();
		return self::$bxClient->getSubPhraseEntitiesIds($queryText, $this->getEntityIdFieldName());
	}

    /*public function printData()
    {
		$this->simpleSearch();
		return self::$bxClient->printData();
    }*/

    public function getRecommendation($widgetType, $widgetName, $amount = 3)
    {
		$recommendations = $this->scopeConfig->getValue('bxRecommendations',$this->scopeStore);
		$recChoices = array();
		if ($widgetType == '') {
			
			$recChoice = new \BxRecommendation($widgetName, 0, $amount);
			if (!empty($_REQUEST['productId'])) {
				$recChoice->setProductContext($this->getEntityIdFieldName(), $_REQUEST['productId']);
			}
			$recChoices[] = $recChoice;
		} else {
			foreach ($recommendations as $recommendation) {
				if (
					(!empty($recommendation['min']) || $recommendation['min'] >= 0) &&
					(!empty($recommendation['max']) || $recommendation['max'] >= 0) &&
					!empty($recommendation['scenario']) &&
					($recommendation['min'] <= $recommendation['max']) &&
					$recommendation['status'] == true
				) {
					if ($recommendation['scenario'] == $widgetType) {
						$recChoice = new \BxRecommendation($recommendation['widget'], $recommendation['min'], $recommendation['max']);
						if ($widgetType === 'basket' && $_REQUEST['basketContent']) {
							$recChoice->setBasketContext($this->getEntityIdFieldName(), json_decode($_REQUEST['basketContent'], true));
						} elseif ($widgetType === 'product' && !empty($_REQUEST['productId'])) {
							$recChoice->setProductContext($this->getEntityIdFieldName(), $_REQUEST['productId']);
						}
						$recChoices[] = $recChoice;
					}
				}
			}
		}
		if (empty($recChoices)) {
			return array();
		}
		
		$returnFields = array('id');
        
		return self::$bxClient->getChoiceRecommendations($widgetName, $recChoices, $returnFields);
    }

}
