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
    private static $choiceContexts = [];

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
     * @var bool
     */
    protected $isNavigation = false;

    /**
     * @var bool
     */
    protected $isSearch = false;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_modelConfig;

    /**
     * @var String
     */
    protected $landingPageChoice = null;

    /**
     * @var string
     */
    protected $prefixContextParameter = '';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Adapter constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Category $catalogCategory
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Search\Model\QueryFactory $queryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Eav\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
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
        \Magento\Eav\Model\Config $config,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_logger = $logger;
        $this->_modelConfig = $config;
        $this->response = $response;
        $this->bxHelperData = $bxHelperData;
        $this->scopeConfig = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
        $this->request = $request;
        $this->registry = $registry;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        if ($this->bxHelperData->isPluginEnabled()) {
            $libPath = __DIR__ . '/../../Lib';
            require_once($libPath . '/BxClient.php');
            \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
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
            $apiKey = $this->scopeConfig->getValue('bxGeneral/general/apiKey', $this->scopeStore);
            $apiSecret = $this->scopeConfig->getValue('bxGeneral/general/apiSecret', $this->scopeStore);
            $domain = $this->scopeConfig->getValue('bxGeneral/general/domain', $this->scopeStore);
            $sendRequestId = (bool) $this->scopeConfig->getValue('bxGeneral/advanced/send_request_id', $this->scopeStore);
            self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password, $this->request->getParams(), $apiKey, $apiSecret);
            self::$bxClient->setTimeout($this->scopeConfig->getValue('bxGeneral/advanced/thrift_timeout', $this->scopeStore));
            self::$bxClient->setSendRequestId($sendRequestId);
            $curl_timeout = $this->scopeConfig->getValue('bxGeneral/advanced/curl_connection_timeout', $this->scopeStore);
            if($curl_timeout != '') {
                self::$bxClient->setCurlTimeout($curl_timeout);
            }

            if($this->request->getControllerName() == 'page') {
                self::$bxClient->addToRequestMap('bx_cms_id', \Magento\Framework\App\ObjectManager::getInstance()->get('\Magento\Cms\Model\Page')->getIdentifier());
            }
        }
    }

    /**
     * @param string $queryText
     * @return array
     */
    public function getSystemFilters($queryText = "", $type='product')
    {
        $filters = [];
        if($type == 'product') {
            if ($queryText == "") {
                $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_visibility_' . $this->bxHelperData->getLanguage(), array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH), true);
            } else {
                $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_visibility_' . $this->bxHelperData->getLanguage(), array(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE, \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG), true);
            }
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_status', array(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED));
        }
        if($type == 'blog'){
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('is_blog', array(1));
        }
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

    public function setLandingPageChoiceId($choice = ''){

        if (!empty($choice)) {
            return $this->landingPageChoice = $choice;
        }

        return $choice;

    }

    /**
     * @param $queryText
     * @param bool $isBlog
     * @return mixed|string
     */
    public function getSearchChoice($queryText, $isBlog = false)
    {
        if($isBlog) {
            $choice = $this->scopeConfig->getValue('bxSearch/advanced/blog_choice_id', $this->scopeStore);
            if ($choice == null) {
                $choice = "read_search";
            }
            return $choice;
        }

        $landingPageChoiceId = $this->landingPageChoice;
        if (!empty($landingPageChoiceId)) {
            $this->currentSearchChoice = $landingPageChoiceId;
            return $landingPageChoiceId;
        }
        $choice = null;
        if (empty($queryText) && $this->isNavigation) {
            $choice = $this->scopeConfig->getValue('bxSearch/advanced/navigation_choice_id', $this->scopeStore);
            if ($choice == null) {
                $choice = "navigation";
            }
            $this->currentSearchChoice = $choice;
            $this->navigation = true;
            return $choice;
        }

        $choice = $this->scopeConfig->getValue('bxSearch/advanced/search_choice_id', $this->scopeStore);
        if (($choice == null && !empty($queryText)) || ($choice==null && $this->isSearch)) {
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
     * @param $queryText
     * @param \Boxalino\Intelligence\Helper\Autocomplete $autocomplete
     * @return array
     */
    public function autocomplete($queryText, \Boxalino\Intelligence\Helper\Autocomplete $autocomplete)
    {
        $data = [];
        if (empty($queryText)) {
            return $data;
        }

        $hash = null;
        $autocomplete_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/limit', $this->scopeStore);
        $products_limit = $this->scopeConfig->getValue('bxSearch/autocomplete/products_limit', $this->scopeStore);
        $searches = $this->bxHelperData->isBlogEnabled() ? array('product', 'blog') : array('product');
        $bxRequests = [];
        foreach ($searches as $search) {
            $isBlog = $search == 'blog';
            $bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->bxHelperData->getLanguage(),
                $queryText, $autocomplete_limit, $products_limit, $this->getAutocompleteChoice(),
                $this->getSearchChoice($queryText, $isBlog)
            );
            $searchRequest = $bxRequest->getBxSearchRequest();
            $returnFields = $isBlog ? $this->bxHelperData->getBlogReturnFields() : array('products_group_id');
            $searchRequest->setReturnFields($returnFields);
            $id = $isBlog ? 'id' : 'products_group_id';
            $searchRequest->setGroupBy($id);
            if(!$isBlog) {
                $searchRequest->setFilters($this->getSystemFilters($queryText, $search));
            }
            $bxRequests[] = $bxRequest;
        }
        self::$bxClient->setAutocompleteRequests($bxRequests);
        self::$bxClient->autocomplete();
        $bxAutocompleteResponses = self::$bxClient->getAutocompleteResponses();

        foreach ($searches as $index => $search) {
            $bxAutocompleteResponse = $bxAutocompleteResponses[$index];
            if($search == 'product'){
                $first = true;
                $global = [];

                $searchChoiceIds = $bxAutocompleteResponse->getBxSearchResponse()->getHitIds($this->currentSearchChoice, true, 0, 10, $this->getEntityIdFieldName());
                $searchChoiceProducts = $autocomplete->getListValues($searchChoiceIds);
                foreach ($searchChoiceProducts as $product) {
                    $row = [];
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
                    $_data = array('title' => $suggestion, 'num_results' => $totalHitcount, 'type' => 'suggestion','id' => $i, 'row_class' => 'acsuggestions');
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
            } else {
                $searchChoiceIds = $bxAutocompleteResponse->getBxSearchResponse()->getHitIds($this->getSearchChoice($queryText, true), true, 0, 10, $this->getEntityIdFieldName());
                $first = true;
                foreach ($searchChoiceIds as $id)  {
                    $blog = [];
                    foreach ($this->bxHelperData->getBlogReturnFields() as $field) {
                        $value = $bxAutocompleteResponse->getBxSearchResponse()->getHitVariable($this->getSearchChoice($queryText, true), $id, $field, 0);
                        $blog[$field] = is_array($value) ? reset($value) : $value;
                        if($field == 'title'){
                            $parts = explode(' ', $blog[$field]);
                            foreach($parts as $pi => $pv) {
                                if(strpos($pv, '&#') !== false) {
                                    $parts[$pi] = mb_convert_encoding($pv, "UTF-8", "HTML-ENTITIES");
                                }
                            }
                            $blog[$field] = implode(' ', $parts);
                        }
                    }
                    $data[] = array('type' => 'blog','product' => $blog, 'first' => $first);
                    if($first) $first = false;
                }
            }
        }

        return $data;
    }

    /**
     * @param $queryText
     * @param int $pageOffset
     * @param $hitCount
     * @param \com\boxalino\bxclient\v1\BxSortFields|null $bxSortFields
     * @param null $categoryId
     * @param bool $addFinder
     */
    public function search($queryText, $pageOffset = 0, $hitCount,  \com\boxalino\bxclient\v1\BxSortFields $bxSortFields = null, $categoryId = null, $addFinder = false)
    {
        $returnFields = array($this->getEntityIdFieldName(), 'categories', 'discountedPrice','products_bx_grouped_price', 'title', 'score');
        $additionalFields = explode(',', $this->scopeConfig->getValue('bxGeneral/advanced/additional_fields', $this->scopeStore));
        if(!empty($additionalFields))
        {
            $returnFields = array_filter(array_merge($returnFields, $additionalFields));
        }

        self::$bxClient->forwardRequestMapAsContextParameters();
        if($addFinder) {
            $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($this->bxHelperData->getLanguage(), $this->getFinderChoice());
            $this->prefixContextParameter = $bxRequest->getRequestWeightedParametersPrefix();
            $this->setPrefixContextParameter($this->prefixContextParameter);
            $bxRequest->setHitsGroupsAsHits(true);
            $bxRequest->addRequestParameterExclusionPatterns('bxi_data_owner');
        } else {
            if(!is_null($this->landingPageChoice)) {
                $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($this->bxHelperData->getLanguage(), $this->landingPageChoice, $hitCount);
            }else {
                $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($this->bxHelperData->getLanguage(), $queryText, $hitCount, $this->getSearchChoice($queryText));
            }
        }
        $bxRequest->setGroupBy('products_group_id');
        $bxRequest->setReturnFields($returnFields);
        $bxRequest->setOffset($pageOffset);
        $bxRequest->setSortFields($bxSortFields);
        $bxRequest->setFacets($this->prepareFacets());
        $bxRequest->setFilters($this->getSystemFilters($queryText));
        $bxRequest->setGroupFacets(true);

        if ($categoryId != null && !$addFinder) {
            $filterField = "category_id";
            $filterValues = array($categoryId);
            $filterNegative = false;
            $bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
        }

        self::$bxClient->addRequest($bxRequest);
        if($this->bxHelperData->isBlogEnabled() && (is_null($categoryId) || !$this->navigation)) {
            $this->addBlogResult($queryText, $hitCount);
        }

        if($this->isOverlyActive()) {
            $this->addOverlayRequests();
        }
    }

    public function isOverlyActive(){
        if ($this->bxHelperData->isOverlayEnabled()) {
            return true;
        }
        return false;
    }

    protected function addBlogResult($queryText, $hitCount) {
        $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($this->bxHelperData->getLanguage(), $queryText, $hitCount, $this->getSearchChoice($queryText, true));
        $requestParams =  $this->request->getParams();
        $pageOffset = isset($requestParams['bx_blog_page'])&&!empty($requestParams['bx_blog_page'])&&is_numeric($requestParams['bx_blog_page']) ? ($requestParams['bx_blog_page'] - 1) * ($hitCount) : 0;
        $bxRequest->setOffset($pageOffset);
        $bxRequest->setGroupBy('id');
        $returnFields = $this->bxHelperData->getBlogReturnFields();
        $bxRequest->setReturnFields($returnFields);
        self::$bxClient->addRequest($bxRequest);
    }

    public function getLandingpageContextParameters($extraParams = null){
        foreach ($extraParams as $key => $value) {
            self::$bxClient->addRequestContextParameter($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getFinderChoice() {
        $choice_id = $this->scopeConfig->getValue('bxSearch/advanced/finder_choice_id', $this->scopeStore);
        if(is_null($choice_id) || $choice_id == '') {
            $choice_id = 'productfinder';
        }
        $this->currentSearchChoice = $choice_id;
        return $choice_id;
    }

    /**
     * @return string
     */
    public function getOverlayChoice() {
        $choice_id = $this->scopeConfig->getValue('bxOverlay/overlay/choice_id', $this->scopeStore);
        if(is_null($choice_id) || $choice_id == '') {
            $choice_id = 'extend';
        }
        $this->currentSearchChoice = $choice_id;
        return $choice_id;
    }

    /**
     * @return string
     */
    public function getOverlayBannerChoice() {
        $choice_id = $this->scopeConfig->getValue('bxOverlay/overlay/banner_choice_id', $this->scopeStore);
        if(is_null($choice_id) || $choice_id == '') {
            $choice_id = 'banner_overlay';
        }
        $this->currentSearchChoice = $choice_id;
        return $choice_id;
    }

    /**
     * @return string
     */
    public function getProfileChoice() {
        $choice_id = $this->scopeConfig->getValue('bxSearch/advanced/profile_choice_id', $this->scopeStore);
        if(is_null($choice_id) || $choice_id == '') {
            $choice_id = 'profile';
        }
        $this->currentSearchChoice = $choice_id;
        return $choice_id;
    }

    /**
     * @param $prefix
     */
    protected function setPrefixContextParameter($prefix){
        $requestParams = $this->request->getParams();
        foreach ($requestParams as $key => $value) {
            if(strpos($key, $prefix) == 0) {
                self::$bxClient->addRequestContextParameter($key, $value);
            }
        }
    }

    /**
     *
     */
    public function simpleSearch($addFinder = false)
    {
        if($this->isNarrative) {
            return;
        }
        $isFinder = $this->bxHelperData->getIsFinder();
        $queryText = $this->getQueryText();
        $choice = $this->getSearchChoice($queryText);
        if(is_null($choice) && !$isFinder && !$addFinder && !$this->bxHelperData->isOverlayEnabled())
        {
            throw new \Exception("Invalid request context: missing choice. Please contact Boxalino with your specific project scenario.");
        }
        if (self::$bxClient->getChoiceIdRecommendationRequest($choice) != null && !$addFinder && !$isFinder) {
            $this->currentSearchChoice = $this->getSearchChoice($queryText);
            return;
        }
        if (self::$bxClient->getChoiceIdRecommendationRequest($this->getFinderChoice()) != null && ($addFinder || $isFinder)) {
            $this->currentSearchChoice = $this->getFinderChoice();
            return;
        }
        $requestParams = $this->request->getParams();
        $sortFields = $this->prepareSortFields($requestParams);
        $categoryId = $this->registry->registry('current_category') != null ? $this->registry->registry('current_category')->getId() : null;
        $hitCount = isset($requestParams['product_list_limit'])&&is_numeric($requestParams['product_list_limit']) ? $requestParams['product_list_limit'] : $this->getMagentoStoreConfigPageSize();
        $pageOffset = isset($requestParams['p'])&&!empty($requestParams['p'])&&is_numeric($requestParams['p']) ? ($requestParams['p'] - 1) * ($hitCount) : 0;

        $this->search($queryText, $pageOffset, $hitCount, $sortFields, $categoryId, $addFinder);
    }

    protected function addNarrativeRequest($choice_id = 'narrative', $choices = null, $replaceMain = true, $hitCount=null, $choicesHitCounts=null, $orderBy=null, $direction=null, $pageOffset=null, $withFacets = true)
    {
        if($replaceMain) {
            $this->currentSearchChoice = $choice_id;
            $this->isNarrative = true;
        }

        $requestParams = $this->request->getParams();
        $sortFields = $this->prepareSortFields($requestParams, $orderBy, $direction);
        if($hitCount == null) {
            $hitCount = isset($requestParams['product_list_limit'])&&is_numeric($requestParams['product_list_limit']) ? $requestParams['product_list_limit'] : $this->getMagentoStoreConfigPageSize();
        }
        if($pageOffset == null) {
            $pageOffset = isset($requestParams['p'])&&!empty($requestParams['p'])&&is_numeric($requestParams['p']) ? ($requestParams['p'] - 1) * ($hitCount) : 0;
        }

        $language = $this->getLanguage();
        $bxRequest = new \com\boxalino\bxclient\v1\BxRequest($language, $choice_id, $hitCount);
        $bxRequest->setOffset($pageOffset);
        $bxRequest->setSortFields($sortFields);
        $bxRequest->setGroupBy('products_group_id');
        $bxRequest->setHitsGroupsAsHits(true);
        $bxRequest->setFilters($this->getSystemFilters());
        if($withFacets && $replaceMain) {
            $facets = $this->prepareFacets();
            $bxRequest->setFacets($facets);
        }
        $bxRequest->setGroupFacets(true);
        $variantId = self::$bxClient->addRequest($bxRequest);

        $requestParams = $this->request->getParams();
        foreach ($requestParams as $key => $value) {
            self::$bxClient->addRequestContextParameter($key, $value);
            if($key == 'choice_id') {
                $choice_ids = explode(',', $value);
                if(is_array($choice_ids)) {
                    foreach ($choice_ids as $choice) {
                        $bxRequest = new \com\boxalino\bxclient\v1\BxRequest($language, $choice, $hitCount);
                        if(strpos($choice, 'banner') !== FALSE) {
                            self::$bxClient->addRequestContextParameter('banner_context', [1]);
                            $bxRequest->setReturnFields(array('title', 'products_bxi_bxi_jssor_slide', 'products_bxi_bxi_jssor_transition', 'products_bxi_bxi_name', 'products_bxi_bxi_jssor_control', 'products_bxi_bxi_jssor_break'));
                        }
                        self::$bxClient->addRequest($bxRequest);
                    }
                }
            }
        }
        if(!is_null($choices)) {
            $choice_ids = explode(',', $choices);
            if(is_array($choice_ids)) {
                foreach ($choice_ids as $choice) {
                    $choiceHitCount = $choicesHitCounts == null || !isset($choicesHitCounts[$choice]) ? $hitCount : $choicesHitCounts[$choice];
                    $bxRequest = new \com\boxalino\bxclient\v1\BxRequest($language, $choice, $choiceHitCount);
                    if(strpos($choice, 'banner') !== FALSE) {
                        self::$bxClient->addRequestContextParameter('banner_context', [1]);
                        $bxRequest->setReturnFields(array('title', 'products_bxi_bxi_jssor_slide', 'products_bxi_bxi_jssor_transition', 'products_bxi_bxi_name', 'products_bxi_bxi_jssor_control', 'products_bxi_bxi_jssor_break'));
                    }
                    self::$bxClient->addRequest($bxRequest);
                }
            }
        }
        return $variantId;
    }

    /**
     * Preparing sort fields
     * Adding extra sort fields if such have been defined/requested
     *
     * @param $requestParams
     * @param $orderBy null | string (title, name, price)
     * @param $direction null | bool
     * @return \com\boxalino\bxclient\v1\BxSortFields
     */
    protected function prepareSortFields($requestParams, $orderBy=null, $direction=null)
    {
        $field = ''; $dir=false;
        if(is_null($orderBy)){
            $orderBy = isset($requestParams['product_list_order']) ? $requestParams['product_list_order'] : $this->getMagentoStoreConfigListOrder();
        }

        $fieldsMapping = $this->bxHelperData->getSortOptionsMapping();
        if(isset($fieldsMapping[$orderBy]))
        {
            $field = array_keys($fieldsMapping[$orderBy])[0];
            $dir = array_values($fieldsMapping[$orderBy])[0] == 'asc' ? false : true;
        }

        if(is_null($direction)) {
            $direction = isset($requestParams['product_list_dir']) ? true : $dir;
        }

        $sortFields = new \com\boxalino\bxclient\v1\BxSortFields();
        if(empty($field)){
            return $sortFields;
        }

        $sortFields->push($field, $direction);
        $extraSortRequests = $this->bxHelperData->getExtraSortFields();
        if(!isset($extraSortRequests[$orderBy])){
            return $sortFields;
        }

        foreach($extraSortRequests[$orderBy] as $extraField=>$direction)
        {
            $reverse = true;
            if(strtoupper($direction) == "DESC") {$reverse = false;}
            $sortFields->push($extraField, $reverse);
        }

        return $sortFields;
    }

    protected $isNarrative = false;
    public function getNarratives($choice_id = 'narrative', $choices = null, $replaceMain = true, $execute = true)
    {
        if(is_null(self::$bxClient->getChoiceIdRecommendationRequest($choice_id))) {
            $this->addNarrativeRequest($choice_id, $choices, $replaceMain);
        }
        if($execute) {
            $dependencies = $this->getResponse()->getNarratives($choice_id);
            return $dependencies;
        }
    }

    public function getNarrativeDependencies($choice_id = 'narrative', $choices = null, $replaceMain = true, $execute = true)
    {
        if(is_null(self::$bxClient->getChoiceIdRecommendationRequest($choice_id))) {
            $this->addNarrativeRequest($choice_id, $choices, $replaceMain);
        }
        if($execute) {
            $dependencies = $this->getResponse()->getNarrativeDependencies($choice_id);
            return $dependencies;
        }
    }

    /**
     * Query string pre-validator
     * @return string
     */
    public function getQueryText()
    {
        $query = $this->queryFactory->get();
        $queryText = $query->getQueryText();
        if($queryText === $this->bxHelperData->getEmptySearchQueryReplacement())
        {
            return "";
        }

        return $queryText;
    }

    /**
     * @return int
     */
    protected function getMagentoRootCategoryId()
    {
        return $this->storeManager->getStore()->getRootCategoryId();
    }

    /**
     * @return mixed
     */
    public function getMagentoStoreConfigPageSize()
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
    protected function getMagentoStoreConfig()
    {
        return $this->scopeConfig->getValue('catalog/frontend');
    }

    /**
     * @return string
     */
    public function getPrefixContextParameter() {
        return $this->prefixContextParameter;
    }

    /**
     * @return string
     */
    public function getUrlParameterPrefix()
    {
        return 'bx_';
    }

    public function getLanguage() {
        return $this->bxHelperData->getLanguage();
    }

    /**
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    protected function prepareFacets()
    {
        $bxFacets = new \com\boxalino\bxclient\v1\BxFacets();
        $selectedValues = [];
        $bxSelectedValues = [];
        $systemParamValues = [];
        $requestParams = $this->request->getParams();
        $attributePrefix = $this->bxHelperData->getProductAttributePrefix();
        $context = $this->navigation ? 'navigation' : 'search';
        $attributeCollection = $this->bxHelperData->getFilterProductAttributes($context);
        $facetOptions = $this->bxHelperData->getFacetOptions();
        $separator = $this->bxHelperData->getSeparator();
        foreach ($requestParams as $key => $values) {
            $additionalChecks = false;
            if (strpos($key, $this->getUrlParameterPrefix()) === 0 && $key != 'bx_category_id') {
                $fieldName = substr($key, 3);
                if (!isset($attributeCollection[$fieldName]) || $key == 'bx_discountedPrice') {
                    $bxSelectedValues[$fieldName] = is_array($values) ? $values : explode($separator, $values);
                } else {
                    $key = substr($fieldName, strlen($attributePrefix), strlen($fieldName));
                    $additionalChecks = true;
                }
            }
            if (isset($attributeCollection[$attributePrefix . $key])) {
                $paramValues = !is_array($values) ? array($values) : $values;
                $attributeModel = $this->_modelConfig->getAttribute('catalog_product', $key)->getSource();
                foreach ($paramValues as $paramValue)
                {
                    $value = html_entity_decode($attributeModel->getOptionText($paramValue), ENT_QUOTES);
                    if($additionalChecks && !$value) {
                        $systemParamValues[$key]['additional'] = $additionalChecks;
                        $paramValue = explode($separator, $paramValue);
                        $optionValues = $attributeModel->getAllOptions(false);
                        foreach ($optionValues as $optionValue) {
                            if(in_array($optionValue['label'], $paramValue)){
                                $selectedValues[$attributePrefix . $key][] = $optionValue['label'];
                                $this->bxHelperData->setRemoveParams('bx_products_' . $key);
                                $systemParamValues[$key]['values'][] = $optionValue['value'];
                            }
                        }
                    }
                    if($value) {
                        $optionParamsValues = explode($separator, $paramValue);
                        foreach ($optionParamsValues as $optionParamsValue) {
                            $systemParamValues[$key]['values'][] = $optionParamsValue;
                        }
                        $value = is_array($value) ? $value : [$value];
                        foreach ($value as $v) {
                            $selectedValues[$attributePrefix . $key][] = $v;
                        }
                    }
                }
            }

            if(in_array($key, $this->bxHelperData->getCustomPropertiesAsSystem()))
            {
                $bxSelectedValues[$attributePrefix . $key] = explode($separator, $values);
            }
        }
        if(sizeof($systemParamValues) > 0) {
            foreach ($systemParamValues as $key => $systemParam) {
                if(isset($systemParam['additional'])){
                    $this->bxHelperData->setSystemParams($key, $systemParam['values']);
                }
            }
        }

        if (!$this->navigation) {
            $values = isset($requestParams['bx_category_id']) ? $requestParams['bx_category_id'] : $this->getMagentoRootCategoryId();
            $values = explode($separator, $values);
            $andSelectedValues = isset($facetOptions['category_id']) ? $facetOptions['category_id']['andSelectedValues']: false;
            $bxFacets->addCategoryFacet($values, 2, -1, $andSelectedValues);
        }

        foreach ($attributeCollection as $code => $attribute)
        {
            if($attribute['addToRequest'] || isset($selectedValues[$code]))
            {
                $bound = $code == 'discountedPrice' ? true : false;
                list($label, $type, $order, $position) = array_values($attribute);
                $selectedValue = isset($selectedValues[$code]) ? $selectedValues[$code] : null;
                if ($code == 'discountedPrice' && isset($bxSelectedValues[$code])) {
                    $bxFacets->addPriceRangeFacet($bxSelectedValues[$code]);
                    unset($bxSelectedValues[$code]);
                } else {
                    $andSelectedValues = isset($facetOptions[$code]) ? $facetOptions[$code]['andSelectedValues']: false;
                    $bxFacets->addFacet($code, $selectedValue, $type, $label, $order, $bound, -1, $andSelectedValues);
                }
            }
        }

        foreach($bxSelectedValues as $field => $values)
        {
            $andSelectedValues = isset($facetOptions[$field]) ? $facetOptions[$field]['andSelectedValues']: false;
            $bxFacets->addFacet($field, $values, 'string', null, 2, false, -1, $andSelectedValues);
        }

        return $bxFacets;
    }

    /**
     *
     */
    public function getClientResponse()
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

    public function getBlogIds()
    {
        try{
            $this->simpleSearch();
            $choice_id = $this->getSearchChoice('', true);
            return $this->getClientResponse()->getHitIds($choice_id, true, 0, 10, $this->getEntityIdFieldName());
        } catch (\Exception $exception)
        {
            $this->_logger->warning("Boxalino P13N  conflict: " . $exception->getMessage());
            return false;
        }
    }

    public function getBlogTotalHitCount()
    {
        $this->simpleSearch();
        $choice_id = $this->getSearchChoice('', true);
        return $this->getClientResponse()->getTotalHitCount($choice_id);
    }

    public function getHitVariable($id, $field, $is_blog=false) {
        $this->simpleSearch();
        $choice_id = $this->currentSearchChoice;
        if($is_blog) {
            $choice_id = $this->getSearchChoice('', true);
        }
        return $this->getClientResponse()->getHitVariable($choice_id, $id, $field, 0);
    }

    /**
     * @return mixed
     */
    public function getTotalHitCount($variant_index = null)
    {
        $this->simpleSearch();
        $choiceId = is_null($variant_index) ? $this->currentSearchChoice : $this->getClientResponse()->getChoiceIdFromVariantIndex($variant_index);
        return $this->getClientResponse()->getTotalHitCount($choiceId, true, 0);
    }

    /**
     * @param null $choiceId
     * @return mixed
     */
    public function getEntitiesIds($variant_index = null)
    {
        $this->simpleSearch();
        $choiceId = is_null($variant_index) ? $this->currentSearchChoice : $this->getClientResponse()->getChoiceIdFromVariantIndex($variant_index);
        return $this->getClientResponse()->getHitIds($choiceId, true, 0, 10, $this->getEntityIdFieldName());
    }

    public function getOverlayVariantId()
    {
        return $this->overlayVariantId;
    }

    public function getOverlayHitcount()
    {
        $hitcount = $this->bxHelperData->getOverlayHitcount();
        if (!empty($hitcount)) {
            return $hitcount;
        }
        return 3;
    }

    public function getOverlayBannerChoiceHitCount()
    {
        $bannerHitcount = $this->bxHelperData->getOverlayBannerChoiceHitcount();
        if (!empty($bannerHitcount)) {
            return $bannerHitcount;
        }
        return 1;
    }

    public function getOverlayOrder()
    {
        $order = $this->bxHelperData->getOverlayOrder();
        if (!empty($order)) {
            return $order;
        }
        return null;
    }

    public function getOverlayDir()
    {
        $dir = $this->bxHelperData->getOverlayDir();
        if (!empty($dir)) {
            return $dir;
        }
        return null;
    }

    public function getOverlayPageOffset()
    {
        $pageoffset = $this->bxHelperData->getOverlayPageOffset();
        if (!empty($pageoffset)) {
            return $pageoffset;
        }
        return null;
    }

    /**
     * @return mixed
     */
    protected $overlayVariantId = null;
    public function addOverlayRequests($hitcount=null, $overlayBannerChoiceHitCount=null, $order=null, $dir=null, $pageOffset=null)
    {
        if($hitcount == null) {
            $hitcount = $this->getOverlayHitcount();
        }

        if($overlayBannerChoiceHitCount == null) {
            $overlayBannerChoiceHitCount = $this->getOverlayBannerChoiceHitCount();
        }

        if($order == null) {
            $order = $this->getOverlayOrder();
        }

        if($dir == null) {
            $dir = $this->getOverlayDir();
        }

        if($pageOffset == null) {
            $pageOffset = $this->getOverlayPageOffset();
        }

        $choicesHitCounts = null;
        if (is_null($this->overlayVariantId)) {
            if($overlayBannerChoiceHitCount != null) {
                $choicesHitCounts[$this->getOverlayBannerChoice()] = $overlayBannerChoiceHitCount;
            }
            $this->overlayVariantId = $this->addNarrativeRequest($this->getOverlayChoice(), $this->getOverlayBannerChoice(), false, $hitcount, $choicesHitCounts, $order, $dir, $pageOffset, false);
        }
    }

    public function sendOverlayRequestWithParams($final=true)
    {
        $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($this->bxHelperData->getLanguage(), $this->getOverlayChoice());
        $this->prefixContextParameter = $bxRequest->getRequestWeightedParametersPrefix();
        $this->setPrefixContextParameter($this->prefixContextParameter);
        self::$bxClient->addRequest($bxRequest);
        if($final)
        {
            self::$bxClient->sendAllChooseRequests();
        }
    }

    /**
     * @param bool $getFinder
     * @return null
     */
    public function getFacets($getFinder = false)
    {
        try{
            $this->simpleSearch($getFinder);
            $facets = $this->getClientResponse()->getFacets($this->currentSearchChoice);
            if (empty($facets)) {
                return null;
            }

            $facets->setParameterPrefix($this->getUrlParameterPrefix());
            return $facets;
        } catch (\Exception $exception) {
            $this->_logger->warning("Boxalino P13N  conflict: " . $exception->getMessage());
            return false;
        }
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
     * @param array $returnFields
     * @return array|void
     * @throws \Exception
     */
    public function getRecommendation($widgetName, $context = array(), $widgetType = '', $minAmount = 3, $amount = 3, $execute = true, $returnFields = array())
    {
        if (!$execute || !isset(self::$choiceContexts[$widgetName])) {
            if (!isset(self::$choiceContexts[$widgetName])) {
                self::$choiceContexts[$widgetName] = [];
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
                    $bxRequest = null;
                    if($widgetType == 'parametrized') {
                        $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($this->bxHelperData->getLanguage(), $widgetName, $amount, $minAmount);
                    } else {
                        $bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->bxHelperData->getLanguage(), $widgetName, $amount, $minAmount);
                    }

                    if ($widgetType != 'blog') {
                        $bxRequest->setGroupBy('products_group_id');
                        $bxRequest->setFilters($this->getSystemFilters());
                    }
                    $bxRequest->setReturnFields(array_merge(array($this->getEntityIdFieldName()), $returnFields));

                    $categoryId = is_null($this->registry->registry('current_category')) ? $this->getMagentoRootCategoryId() : $this->registry->registry('current_category')->getId();
                    self::$bxClient->addRequestContextParameter('current_category_id', $categoryId);

                    if ($widgetType === 'basket' && is_array($context)) {
                        $basketProducts = [];
                        foreach ($context as $product) {
                            $basketProducts[] = array('id' => $product->getId(), 'price' => $product->getPrice());
                        }
                        $bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
                    } elseif (($widgetType === 'product' || $widgetType === 'blog') && !is_array($context)) {
                        $product = $context;
                        $bxRequest->setProductContext($this->getEntityIdFieldName(), $product->getId());
                    } elseif ($widgetType === 'category' && $context != null) {
                        $filterField = "category_id";
                        $filterValues = is_array($context) ? $context : array($context);
                        $filterNegative = false;
                        $bxRequest->addFilter(new BxFilter($filterField, $filterValues, $filterNegative));
                    } elseif ($widgetType === 'banner' || $widgetType === 'banner_small') {
                        $bxRequest->setGroupBy('id');
                        $bxRequest->setFilters(array());
                        $contextValues = is_array($context) ? $context : array($context);
                        self::$bxClient->addRequestContextParameter('banner_context', $contextValues);
                    }
                    self::$bxClient->addRequest($bxRequest);
                }
            }
            if($this->isOverlyActive()) {
                $this->addOverlayRequests();
            }
            return [];
        }
        $count = array_search(json_encode(array($context)), self::$choiceContexts[$widgetName]);
        return $this->getClientResponse()->getHitIds($widgetName, true, $count, 10, $this->getEntityIdFieldName());
    }

    /**
     * @param $warning
     */
    public function notifyWarning($warning) {
        self::$bxClient->notifyWarning($warning);
    }


    public function addNotification($type, $notification) {
        self::$bxClient->addNotification($type, $notification);
    }

    /**
     * @param bool $force
     * @param string $requestMapKey
     */
    public function finalNotificationCheck($force = false, $requestMapKey = 'dev_bx_notifications') {
        if(!is_null(self::$bxClient)) {
            $output = self::$bxClient->finalNotificationCheck($force, $requestMapKey);
            if($output != '') {
                $this->response->appendBody($output);
            }
        }
    }

    public function getResponse()
    {
        $this->simpleSearch();
        $response = $this->getClientResponse();
        return $response;
    }

    public function getSEOPageTitle($choice = null){
        if ($this->bxHelperData->isPluginEnabled()) {
            $seoPageTitle = $this->getExtraInfoWithKey('bx-page-title', $choice);
            return $seoPageTitle;
        }
        return;
    }

    public function getSEOMetaTitle($choice = null){
        if ($this->bxHelperData->isPluginEnabled()) {
            $seoMetaTitle = $this->getExtraInfoWithKey('bx-html-meta-title', $choice);
            return $seoMetaTitle;
        }
        return;
    }

    public function getSEOMetaDescription($choice = null){
        if ($this->bxHelperData->isPluginEnabled()) {
            $seoMetaDescription = $this->getExtraInfoWithKey('bx-html-meta-description', $choice);
            return $seoMetaDescription;
        }
        return;
    }

    public function getExtraInfoWithKey($key, $choice = null){
        if ($this->bxHelperData->isPluginEnabled() && !empty($key)) {
            $choice = is_null($choice) ? $this->currentSearchChoice : $choice;
            $extraInfo = $this->getResponse()->getExtraInfo($key, '', $choice);
            return $extraInfo;
        }
        return;
    }

    /**
     * Creating a request with params to boxalino server
     * Used when the context params contain data needed to be synced
     *
     * @param $choice
     * @param array $params
     */
    public function sendRequestWithParams($choice, $params=array(), $final=false, $hitCount = 0)
    {
        $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($this->bxHelperData->getLanguage(), $choice, $hitCount);
        $this->prefixContextParameter = $bxRequest->getRequestWeightedParametersPrefix();
        $this->setPrefixContextParameter($this->prefixContextParameter, $params);
        self::$bxClient->addRequest($bxRequest);
        if($final)
        {
            self::$bxClient->sendAllChooseRequests();
        }
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsNavigation($value)
    {
        $this->isNavigation = $value;
        return $this;
    }

    /**
     * clear prior requests (ex: in case of noresults)
     */
    public function flushResponses()
    {
        self::$bxClient->flushResponses();
        self::$bxClient->resetRequests();
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsSearch($value)
    {
        $this->isSearch = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsNarrative($value)
    {
        $this->isNarrative = $value;
        return $this;
    }

    /**
     * Simple request on distinct narrative as array via configuration
     *
     * @param $choiceConfiguration
     * @return $this
     */
    public function setNarrativeChoices($choiceConfiguration)
    {
        foreach($choiceConfiguration as $choice)
        {
            if(is_null(self::$bxClient->getChoiceIdRecommendationRequest($choice->getName()))) {
                $this->addSingleNarrativeRequest($choice->getName(), $choice->getHitCount(), $choice->getOffset(), $choice->getSort(), $choice->getOrder(), $choice->getWithFacets());
            }
        }

        $requestParams = $this->request->getParams();
        foreach ($requestParams as $key => $value) {
            self::$bxClient->addRequestContextParameter($key, $value);
        }

        $narrativeContent = [];
        foreach($choiceConfiguration as $choice)
        {
            $narrativeContent[$choice->getVariant()] = $this->getNarrativeResponse($choice);
        }

        return $narrativeContent;
    }

    public function getNarrativeResponse($choice)
    {
        return $this->getClientResponse()->getNarratives($choice->getName());
    }

    public function getNarrativeDependenciesResponse($choice)
    {
        return $this->getClientResponse()->getNarrativeDependencies($choice->getName());
    }

    public function addSingleNarrativeRequest($choice_id, $hitCount, $pageOffset, $orderBy=null, $direction=null, $withFacets = true)
    {
        $requestParams = $this->request->getParams();
        $sortFields = $this->prepareSortFields($requestParams, $orderBy, $direction);
        $language = $this->getLanguage();
        $bxRequest = new \com\boxalino\bxclient\v1\BxRequest($language, $choice_id, $hitCount);
        $bxRequest->setOffset($pageOffset);
        $bxRequest->setSortFields($sortFields);
        $bxRequest->setGroupBy('products_group_id');
        $bxRequest->setHitsGroupsAsHits(true);
        $bxRequest->setFilters($this->getSystemFilters());
        if($withFacets) {
            $facets = $this->prepareFacets();
            $bxRequest->setFacets($facets);
        }
        $bxRequest->setGroupFacets(true);
        self::$bxClient->addRequest($bxRequest);

        return $this;
    }
}
