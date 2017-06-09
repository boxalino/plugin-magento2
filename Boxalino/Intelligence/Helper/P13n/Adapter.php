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
     * @var array
     */
    private static $choiceContexts = array();

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
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    protected $queryFactory;

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
     * @var bool
     */
    protected $bxDebug = false;

    /**
     * @var bool
     */
    protected $navigation = false;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_modelConfig;

    /**
     * Adapter constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Category $catalogCategory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Search\Model\QueryFactory $queryFactory
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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\App\Response\Http $response,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Eav\Model\Config $config

    )
    {
        $this->_modelConfig = $config;
        $this->response = $response;
        $this->bxHelperData = $bxHelperData;
        $this->scopeConfig = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
        $this->request = $request;
        $this->registry = $registry;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;

        $libPath = __DIR__ . '/../../Lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
        if ($this->bxHelperData->isPluginEnabled()) {
            $this->initializeBXClient();
        }
    }

    /**
     * Initializes the \com\boxalino\bxclient\v1\BxClient
     */
    protected function initializeBXClient()
    {

        if (self::$bxClient == null) {

            $account = $this->scopeConfig->getValue('bxGeneral/general/account_name', $this->scopeStore);
            $password = $this->scopeConfig->getValue('bxGeneral/general/password', $this->scopeStore);
            $isDev = $this->scopeConfig->getValue('bxGeneral/general/dev', $this->scopeStore);
            $host = $this->scopeConfig->getValue('bxGeneral/advanced/host', $this->scopeStore);
            $p13n_username = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_username', $this->scopeStore);
            $p13n_password = $this->scopeConfig->getValue('bxGeneral/advanced/p13n_password', $this->scopeStore);
            $domain = $this->scopeConfig->getValue('bxGeneral/general/domain', $this->scopeStore);
            self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
            self::$bxClient->setTimeout($this->scopeConfig->getValue('bxGeneral/advanced/thrift_timeout', $this->scopeStore))->setRequestParams($this->request->getParams());
        }
    }

    /**
     * @param string $queryText
     * @return array
     */
    public function getSystemFilters($queryText = "")
    {

        $filters = array();
        if ($queryText == "") {
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
    public function getAutocompleteChoice()
    {

        $choice = $this->scopeConfig->getValue('bxSearch/advanced/autocomplete_choice_id', $this->scopeStore);
        if ($choice == null) {
            $choice = "autocomplete";
        }
        return $choice;
    }

    /**
     * @param $queryText
     * @return mixed|string
     */
    public function getSearchChoice($queryText)
    {

        if ($queryText == null) {
            $choice = $this->scopeConfig->getValue('bxSearch/advanced/navigation_choice_id', $this->scopeStore);
            if ($choice == null) {
                $choice = "navigation";
            }
            $this->currentSearchChoice = $choice;
            $this->navigation = true;
            return $choice;
        }

        $choice = $this->scopeConfig->getValue('bxSearch/advanced/search_choice_id', $this->scopeStore);
        if ($choice == null) {
            $choice = "search";
        }
        $this->currentSearchChoice = $choice;
        return $choice;
    }

    /**
     * @return mixed|string
     */
    public function getEntityIdFieldName()
    {

        $entityIdFieldName = $this->scopeConfig->getValue('bxGeneral/advanced/entity_id', $this->scopeStore);
        if (!isset($entityIdFieldName) || $entityIdFieldName === '') {
            $entityIdFieldName = 'products_group_id';
        }
        return $entityIdFieldName;
    }

    /**
     * @param string $queryText
     * @param \Boxalino\Intelligence\Helper\Autocomplete $autocomplete
     * @return array
     */
    public function autocomplete($queryText, \Boxalino\Intelligence\Helper\Autocomplete $autocomplete)
    {

        $order = array();
        $data = array();
        $hash = null;

        $autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit', $this->scopeStore);
        $products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit', $this->scopeStore);

        if ($queryText) {

            $bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->bxHelperData->getLanguage(),
                $queryText, $autocomplete_limit, $products_limit, $this->getAutocompleteChoice(),
                $this->getSearchChoice($queryText)
            );
            $searchRequest = $bxRequest->getBxSearchRequest();

            $searchRequest->setReturnFields(array('products_group_id'));
            $searchRequest->setGroupBy('products_group_id');
            $searchRequest->setFilters($this->getSystemFilters($queryText));
            self::$bxClient->setAutocompleteRequest($bxRequest);
            self::$bxClient->autocomplete();
            $bxAutocompleteResponse = self::$bxClient->getAutocompleteResponse();

            $first = true;
            $global = [];

            $searchChoiceIds = $bxAutocompleteResponse->getBxSearchResponse()->getHitIds($this->currentSearchChoice, true, 0, 10, $this->getEntityIdFieldName());
            $searchChoiceProducts = $autocomplete->getListValues($searchChoiceIds);

            foreach ($searchChoiceProducts as $product) {
                $row = array();
                $row['type'] = 'global_products';
                $row['row_class'] = 'suggestion-item global_product_suggestions';
                $row['product'] = $product;
                $row['first'] = $first;
                $first = false;
                $global[] = $row;

            }

            $suggestions = [];
            $suggestionProducts = [];

            foreach ($bxAutocompleteResponse->getTextualSuggestions() as $i => $suggestion) {

                $totalHitcount = $bxAutocompleteResponse->getTextualSuggestionTotalHitCount($suggestion);

                if ($totalHitcount <= 0) {
                    continue;
                }

                $_data = array('title' => $suggestion, 'num_results' => $totalHitcount, 'type' => 'suggestion',
                    'id' => $i, 'row_class' => 'acsuggestions');

                $suggestionProductIds = $bxAutocompleteResponse->getBxSearchResponse($suggestion)->getHitIds($this->currentSearchChoice, true, 0, 10, $this->getEntityIdFieldName());
                $suggestionProductValues = $autocomplete->getListValues($suggestionProductIds);
                foreach ($suggestionProductValues as $product) {
                    $suggestionProducts[] = array("type" => "sub_products", "product" => $product,
                        'row_class' => 'suggestion-item sub_product_suggestions sub_id_' . $i);
                }

                if ($_data['title'] == $queryText) {
                    array_unshift($suggestions, $_data);
                } else {
                    $suggestions[] = $_data;
                }
            }

            $data = array_merge($suggestions, $global);
            $data = array_merge($data, $suggestionProducts);
        }
        return $data;
    }

    /***
     * @param $queryText
     * @param int $pageOffset
     * @param $hitCount
     * @param \com\boxalino\bxclient\v1\BxSortFields|null $bxSortFields
     * @param null $categoryId
     */
    public function search($queryText, $pageOffset = 0, $hitCount, \com\boxalino\bxclient\v1\BxSortFields $bxSortFields = null, $categoryId = null)
    {

        $returnFields = array($this->getEntityIdFieldName(), 'categories', 'discountedPrice', 'title', 'score');
        $additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields', $this->scopeStore));
        $returnFields = array_merge($returnFields, $additionalFields);

        //create search request
        $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($this->bxHelperData->getLanguage(), $queryText, $hitCount, $this->getSearchChoice($queryText));
        $bxRequest->setGroupBy('products_group_id');
        $bxRequest->setReturnFields($returnFields);
        $bxRequest->setOffset($pageOffset);
        $bxRequest->setSortFields($bxSortFields);
        $bxRequest->setFacets($this->prepareFacets());
        $bxRequest->setFilters($this->getSystemFilters($queryText));

        if ($categoryId != null) {
            $filterField = "category_id";
            $filterValues = array($categoryId);
            $filterNegative = false;
            $bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
        }

        self::$bxClient->addRequest($bxRequest);
    }

    /**
     *
     */
    public function simpleSearch()
    {

        $query = $this->queryFactory->get();
        $queryText = $query->getQueryText();

        if (self::$bxClient->getChoiceIdRecommendationRequest($this->getSearchChoice($queryText)) != null) {
            return;
        }

        $requestParams = $this->request->getParams();
        $field = '';
        $order = isset($requestParams['product_list_order']) ? $requestParams['product_list_order'] : $this->getMagentoStoreConfigListOrder();

        if (($order == 'title') || ($order == 'name')) {
            $field = 'products_bx_parent_title';
        } elseif ($order == 'price') {
            $field = 'products_bx_grouped_price';
        }

        $dir = isset($requestParams['product_list_dir']) ? true : false;
        $categoryId = $this->registry->registry('current_category') != null ? $this->registry->registry('current_category')->getId() : null;
        $hitCount = isset($requestParams['product_list_limit']) ? $requestParams['product_list_limit'] : $this->getMagentoStoreConfigPageSize();
        $pageOffset = isset($requestParams['p']) ? ($requestParams['p'] - 1) * ($hitCount) : 0;
        $this->search($queryText, $pageOffset, $hitCount, new \com\boxalino\bxclient\v1\BxSortFields($field, $dir), $categoryId);
    }

    /**
     * @return mixed
     */
    protected function getMagentoStoreConfigPageSize()
    {

        $storeConfig = $this->getMagentoStoreConfig();
        $storeDisplayMode = $storeConfig['list_mode'];

        //we may get grid-list, list-grid, grid or list
        $storeMainMode = explode('-', $storeDisplayMode);
        $storeMainMode = $storeMainMode[0];
        $hitCount = $storeConfig[$storeMainMode . '_per_page'];
        return $hitCount;
    }

    /**
     * @return mixed
     */
    protected function getMagentoStoreConfigListOrder()
    {

        $storeConfig = $this->getMagentoStoreConfig();
        return $storeConfig['default_sort_by'];
    }

    /**
     * @return mixed
     */
    private function getMagentoStoreConfig()
    {

        return $this->scopeConfig->getValue('catalog/frontend');
    }

    /**
     * @return string
     */
    private function getUrlParameterPrefix()
    {

        return 'bx_';
    }

    /**
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    private function prepareFacets()
    {

        $bxFacets = new \com\boxalino\bxclient\v1\BxFacets();
        $selectedValues = array();
        $requestParams = $this->request->getParams();
        $attributeCollection = $this->bxHelperData->getFilterProductAttributes();
        foreach ($requestParams as $key => $values) {
            if (strpos($key, $this->getUrlParameterPrefix()) === 0) {
                $fieldName = substr($key, 3);
                $selectedValues[$fieldName] = $values;
            }
            if (isset($attributeCollection['products_' . $key])) {
                $paramValues = !is_array($values) ? array($values) : $values;
                $attributeModel = $this->_modelConfig->getAttribute('catalog_product', $key)->getSource();
                foreach ($paramValues as $paramValue) {
                    $selectedValues['products_' . $key][] = $attributeModel->getOptionText($paramValue);
                }
            }
        }

        if (!$this->navigation) {
            $catId = isset($selectedValues['category_id']) ? $selectedValues['category_id'] : 2;
            $bxFacets->addCategoryFacet($catId);
        }

        foreach ($attributeCollection as $code => $attribute) {
            $bound = $code == 'discountedPrice' ? true : false;
            list($label, $type, $order, $position) = array_values($attribute);
            $selectedValue = isset($selectedValues[$code]) ? $selectedValues[$code][0] : null;
            if ($code == 'discountedPrice' && isset($selectedValues[$code])) {
                $bxFacets->addPriceRangeFacet($selectedValues[$code]);
            } else {
                $bxFacets->addFacet($code, $selectedValue, $type, $label, $order, $bound);
            }
        }
        return $bxFacets;
    }

    /**
     *
     */
    protected function getClientResponse()
    {
        try {
            $response = self::$bxClient->getResponse();
            $output = self::$bxClient->getDebugOutput();
            if ($output != '' && !$this->bxDebug) {
                $this->response->appendBody($output);
                $this->bxDebug = true;
            }
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @return mixed
     */
    public function getTotalHitCount()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getTotalHitCount($this->currentSearchChoice);
    }

    /**
     * @return mixed
     */
    public function getEntitiesIds()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getHitIds($this->currentSearchChoice, true, 0, 10, $this->getEntityIdFieldName());
    }

    /**
     * @return null
     */
    public function getFacets()
    {

        $this->simpleSearch();
        $facets = $this->getClientResponse()->getFacets($this->currentSearchChoice);
        if (empty($facets)) {
            return null;
        }

        $facets->setParameterPrefix($this->getUrlParameterPrefix());
        return $facets;
    }

    /**
     * @return mixed
     */
    public function getCorrectedQuery()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getCorrectedQuery($this->currentSearchChoice);
    }

    /**
     * @return mixed
     */
    public function areResultsCorrected()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->areResultsCorrected($this->currentSearchChoice);
    }

    /**
     * @return mixed
     */
    public function areThereSubPhrases()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->areThereSubPhrases($this->currentSearchChoice);
    }

    /**
     * @return mixed
     */
    public function getSubPhrasesQueries()
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getSubPhrasesQueries($this->currentSearchChoice);
    }

    /**
     * @param $queryText
     * @return mixed
     */
    public function getSubPhraseTotalHitCount($queryText)
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getSubPhraseTotalHitCount($queryText, $this->currentSearchChoice);
    }

    /**
     * @param $queryText
     * @return mixed
     */
    public function getSubPhraseEntitiesIds($queryText)
    {

        $this->simpleSearch();
        return $this->getClientResponse()->getSubPhraseHitIds($queryText, $this->currentSearchChoice, 0, $this->getEntityIdFieldName());
    }

    /**
     * @param $widgetName
     * @param array $context
     * @param string $widgetType
     * @param int $minAmount
     * @param int $amount
     * @param bool $execute
     * @return array|void
     */
    public function getRecommendation($widgetName, $context = array(), $widgetType = '', $minAmount = 3, $amount = 3, $execute = true)
    {

        if (!$execute) {
            if (!isset(self::$choiceContexts[$widgetName])) {
                self::$choiceContexts[$widgetName] = array();
            }
            if (in_array(json_encode($context), self::$choiceContexts[$widgetName])) {
                return;
            }
            self::$choiceContexts[$widgetName][] = json_encode($context);
            if ($widgetType == '') {
                $bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->bxHelperData->getLanguage(), $widgetName, $amount);
                $bxRequest->setGroupBy('products_group_id');
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
                    $bxRequest->setGroupBy('products_group_id');
                    $bxRequest->setFilters($this->getSystemFilters());
                    $bxRequest->setReturnFields(array($this->getEntityIdFieldName()));
                    if ($widgetType === 'basket' && is_array($context)) {
                        $basketProducts = array();
                        foreach ($context as $product) {
                            $basketProducts[] = array('id' => $product->getId(), 'price' => $product->getPrice());
                        }
                        $bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
                    } elseif ($widgetType === 'product' && !is_array($context)) {
                        $product = $context;
                        $bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
                    } elseif ($widgetType === 'category' && $context != null) {
                        $filterField = "category_id";
                        $filterValues = is_array($context) ? $context : array($context);
                        $filterNegative = false;
                        $bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
                    }
                    self::$bxClient->addRequest($bxRequest);
                }
            }
            return array();
        }
        $count = array_search(json_encode(array($context)), self::$choiceContexts[$widgetName]);
        return $this->getClientResponse()->getHitIds($widgetName, true, $count, 10, $this->getEntityIdFieldName());
    }

    public function notifyWarning($warning) {
        self::$bxClient->notifyWarning($warning);
    }

    public function finalNotificationCheck($force = false, $requestMapKey = 'dev_bx_notifications') {
        self::$bxClient->finalNotificationCheck($force, $requestMapKey);
    }
}