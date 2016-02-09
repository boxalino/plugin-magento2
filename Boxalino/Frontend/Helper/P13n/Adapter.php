<?php
namespace Boxalino\Frontend\Helper\P13n;
use Boxalino\Frontend\Lib\vendor\Thrift\HttpP13n;

class Adapter
{
    private static $bxClient = null;
    
	protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
	protected $catalogCategory;
    protected $session;
    protected $scopeConfig;
    protected $request;
    protected $registry;
    protected $queryFactory;
	protected $p13n;

    public function __construct(
        \Magento\Framework\ObjectManager\ObjectManager $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Framework\Session\Storage $session,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        \Boxalino\Frontend\Helper\Data $bxHelperData,
        \Magento\Search\Model\QueryFactory $queryFactory
    )
    {
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
		$this->request = $request;
		$this->registry = $registry;
		$this->queryFactory = $queryFactory;
		
        $this->p13n =  $objectManager->create('\Boxalino\Frontend\Lib\vendor\Thrift\HttpP13n');
		$this->initializeBXClient($this->p13n);

    }
	
	protected function initializeBXClient($p13n) {
		
		if(self::$bxClient == null) {
			
			$account = $this->scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
			$isDev = $this->scopeConfig->getValue('bxGeneral/general/dev',$this->scopeStore);
			$host = $this->scopeConfig->getValue('bxGeneral/advanced/host',$this->scopeStore);
			$p13n_username = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_username',$this->scopeStore);
			$p13n_password = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_password',$this->scopeStore);
			$domain = $this->scopeConfig->getValue('bxGeneral/advanced/domain',$this->scopeStore);
			$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/general/additional_fields',$this->scopeStore));
			
			$language = substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);
			
			self::$bxClient = new \BxClient($account, $isDev, $host, $p13n_username, $p13n_password, $domain, $language, $additionalFields, $p13n);
			self::$bxClient->addBxFilter(new \BxFilter('products_visibility', array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG), true));
			self::$bxClient->addBxFilter(new \BxFilter('products_status', array(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)));
			
		}
	}
	
	public function resetSearchAdapter() {
		self::$bxClient = null;
		$this->initializeBXClient();
	}
	
	public function getAutocompleteChoice() {
		
		$choice = $this->scopeConfig->getValue('bxSearch/search/autocomplete_choice',$this->scopeStore);
		if($choice == null) {
			$choice = "autocomplete";
		}
		return $choice;
	}
	
	public function getSearchChoice() {
		
		$choice = $this->scopeConfig->getValue('bxSearch/search/search_choice',$this->scopeStore);
		if($choice == null) {
			$choice = "search";
		}
		return $choice;
	}

    public function __destruct()
    {
        unset($this->p13n);
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
		$entityIdFieldName = $this->scopeConfig->getValue('bxSearch/search/entity_id',$this->scopeStore);
		if (!isset($entity_id) || $entity_id === '') {
			$entityIdFieldName = 'entity_id';
		}
		return $entityIdFieldName;
	}
	
	public function autocomplete($queryText) {
		$order = array();
		$hash = null;
		
		$data = array();
		
		$acExtraEnabled = $this->scopeConfig->getValue('bxSearch/autocomplete/enabled',$this->scopeStore);
		$autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit',$this->scopeStore);
		$products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit',$this->scopeStore);
		$acItems = $this->scopeConfig->getValue('bxSearch/autocomplete/items',$this->scopeStore);
			
		if ($queryText) {
			$fields = array($this->getEntityIdFieldName(), 'title', 'score');

			self::$bxClient->autocomplete($queryText, $autocomplete_limit, $products_limit, $fields, $acExtraEnabled, $this->getAutocompleteChoice(), $this->getSearchChoice());
			
			$collection = self::$bxClient->getAutocompleteEntities($acItems);
			
			$hash = self::$bxClient->getACPrefixSearchHash();
			
			$counter = 0;
            foreach ($collection as $item) {
                if ($item['hits'] <= 0) {
                    continue;
                }

                $_data = array(
                    'id' => substr(md5($item['text']), 0, 10),
                    'title' => $item['text'],
                    'html' => $item['html'],
                    'row_class' => (++$counter) % 2 ? 'odd' : 'even',
                    'num_of_results' => $item['hits'],
                    'facets' => $item['facets']
                );

                if ($item['text'] == $query) {
                    array_unshift($data, $_data);
                } else {
                    $data[] = $_data;
                }
            }
		}
		
        $facets = array_key_exists(0, $data) && is_array($data[0]['facets']) ? $data[0]['facets'] : array();
		
		$products = self::$bxClient->getAutocompleteProducts($facets, $this->getEntityIdFieldName());
		
		return array($data, $products, $order, $hash);
	}

    public function search($queryText, $pageOffset = 0, $overwriteHitcount = null, $bxSortFields=null)
    {
		$returnFields = array($this->getEntityIdFieldName(), 'categories', 'discountedPrice', 'title', 'score');
		$additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/general/additional_fields',$this->scopeStore));
		$returnFields = array_merge($returnFields, $additionalFields);
        $searchChoice = $this->getSearchChoice();
		$withRelaxation = $this->scopeConfig->getValue('bxSearch/advanced/relaxation_enabled',$this->scopeStore);
		$bxFacets = $this->prepareFacets();
		
		$hitCount = $overwriteHitcount != null ? $overwriteHitcount : $this->scopeConfig->getValue('bxSearch/search/limit',$this->scopeStore);
		if($hitCount == null) {
			$hitCount = 10;
		}
		
		$offset = $pageOffset * $hitCount;
		
		self::$bxClient->search($queryText, $hitCount, $returnFields, $searchChoice, $bxFacets, $offset, $bxSortFields, $withRelaxation);
		
		$additionalData = self::$bxClient->getAdditionalData($additionalFields);
        $this->session->setData('boxalino_additional_data', $additionalData);
    }
	
	private $simpleSearchDone = false;
	public function simpleSearch() {
		
		if($this->simpleSearchDone) {
			return;
		}
		$this->simpleSearchDone = true;
		
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

		$categoryId = $this->request->getParam('bx_category_id');
		if (empty($categoryId)) {
			/* @var $category Mage_Catalog_Model_Category */
			$category = $this->registry->registry('current_category');
			if (!empty($category)) {
				$_REQUEST['bx_category_id'][0] = $category->getId();
			}
			// GET param 'cat' may override the current_category,
			// i.e. when clicking on subcategories in a category page
			$cat = $this->request->getParam('cat');
			if (!empty($cat)) {
				$_REQUEST['bx_category_id'][0] = $cat;
			}
		}

		$overWriteLimit = (int) $this->request->getParam('limit',null);
		$pageOffset = abs(((int) $this->request->getParam('p', 1)) - 1);

		$query = $this->queryFactory->get();
		
		$this->search($query->getQueryText(), $pageOffset, $overWriteLimit, new \BxSortFields($field, $dir));
            
	}

    private function prepareFacets()
    {
        $normalFilters = array();
        $topFilters = array();
        $enableLeftFilters = $this->scopeConfig->getValue('bxSearch/facets/left_filters_enable',$this->scopeStore);
        $enableTopFilters = $this->scopeConfig->getValue('bxSearch/facets/top_filters_enable',$this->scopeStore);

        if ($enableLeftFilters == 1) {
            $normalFilters = explode(',', $this->scopeConfig->getValue('bxSearch/facets/left_filters_normal',$this->scopeStore));
        }
        if ($enableTopFilters == 1) {
            $topFilters = explode(',', $this->scopeConfig->getValue('bxSearch/facets/top_filters',$this->scopeStore));
        }
		
        if (array_key_exists('bx_category_id', $_REQUEST)) {
            $normalFilters[] = 'category_id:hierarchical:1';
        }
        
		$requestFacets = array();
		foreach ($_REQUEST as $key => $values) {
			if (strpos($key, 'bx_') !== false) {
				$fieldName = substr($key, 3);
				$values = !is_array($values)?array($values):$values;
				foreach ($values as $value) {
					$requestFacets[$fieldName][] = $value;
				}
			}
        }
		$bxFacets = new \BxFacets($requestFacets);
		
		if (count($normalFilters)) {
            foreach ($normalFilters as $filterString) {
                $filter = explode(':', $filterString);
                if ($filter[0] != '') {
					if(!isset($filter[1])) {
						throw new \Exception("a left search filter must be defined with the pattern: FIELD_NAME:TYPE:ORDER (1), wrongly provided filter. " . $filterString);
					}
					if(!isset($filter[2])) {
						throw new \Exception("a left search filter must be defined with the pattern: FIELD_NAME:TYPE:ORDER (2), wrongly provided filter. " . $filterString);
					}
					$bxFacets->addFacet($filter[0], $filter[1], $filter[2]);
                }
            }
        }
        if (count($topFilters)) {
            foreach ($topFilters as $filter) {
                if ($filter != '') {
					$bxFacets->addFacet($filter, 'string', null);
                }
            }
        }
        return $bxFacets;
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

    public function getFacetsData()
    {
		$this->simpleSearch();
		return self::$bxClient->getFacetsData();
    }

    public function getChoiceRelaxation()
    {
		$this->simpleSearch();
		return self::$bxClient->getChoiceRelaxation();
    }

    public function printData()
    {
		$this->simpleSearch();
		return self::$bxClient->printData();
    }

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
