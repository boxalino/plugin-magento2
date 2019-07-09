<?php
namespace Boxalino\Intelligence\Helper;

/**
 * Class Data
 * @package Boxalino\Intelligence\Helper
 */
class Data
{

    /**
     * @var
     */
    protected $reader;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $config;

    /**
     * @var
     */
    protected $catalogProduct;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $factory;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $catalogCategory;

    /**
     * @var \Magento\CatalogSearch\Helper\Data
     */
    protected $catalogSearch;

    /**
     * @var \Magento\Framework\App\FrontControllerInterface
     */
    protected $controllerInterface;

    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var array
     */
    protected static $SCRIPTS = [];

    /**
     * @var array
     */
    protected $bxConfig = [];

    /**
     * @var bool
     */
    protected $setup = true;

    /**
     * @var array
     */
    protected $cmsBlock = [];

    /**
     * @var array
     */
    protected $removedAttributes = [];

    /**
     * @var
     */
    protected $fallback = false;

    /**
     * @var array
     */
    protected $bx_filter = [];

    /**
     * @var bool
     */
    protected $isFinder = false;

    /**
     * @var array
     */
    protected $removeParams = [];

    /**
     * @var array
     */
    protected $systemParams = [];

    /**
     * Data constructor.
     * @param \Magento\CatalogSearch\Helper\Data $catalogSearch
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Category $catalogCategory
     * @param \Magento\Catalog\Model\ResourceModel\Product $catalogProduct
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory
     * @param \Magento\Framework\App\FrontControllerInterface $controllerInterface
     */
    public function __construct(
        \Magento\CatalogSearch\Helper\Data $catalogSearch,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory,
        \Magento\Framework\App\FrontControllerInterface $controllerInterface
    ){
        $this->controllerInterface = $controllerInterface;
        $this->catalogCategory = $catalogProduct;
        $this->config = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->factory = $factory;
        $this->catalogSearch = $catalogSearch;
    }

    /**
     * @return string language code
     */
    public function getLanguage() {
        return substr($this->config->getValue('general/locale/code',$this->scopeStore), 0, 2);
    }

    /**
     * @param $term
     * @param null $filters
     * @return string
     */
    public function reportSearch($term, $filters = null)
    {
        $script = "";
        if ($this->isTrackerEnabled()) {
            $logTerm = addslashes($term);
            $script = "_bxq.push(['trackSearch', '" . $logTerm . "', " . json_encode($filters) . "]);" . PHP_EOL;
        }

        return $script;
    }

    /**
     * @param $product
     * @return string
     */
    public function reportProductView($product)
    {
        $script = "";
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackProductView', '" . $product . "'])" . PHP_EOL;
        }

        return $script;
    }

    /**
     * @param $product
     * @param $count
     * @param $price
     * @param $currency
     * @return string
     */
    public function reportAddToBasket($product, $count, $price, $currency)
    {
        $script = "";
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);" . PHP_EOL;
        }
        return $script;
    }

    /**
     * @param $categoryID
     * @return string
     */
    public function reportCategoryView($categoryID)
    {
        $script = "";
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackCategoryView', '" . $categoryID . "'])" . PHP_EOL;
        }
        return $script;
    }

    /**
     * @param $customerId
     * @return string
     */
    public function reportLogin($customerId)
    {
        $script = "";
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackLogin', '" . $customerId . "'])" . PHP_EOL;
        }

        return $script;
    }

    /**
     * @param $products array example:
     *      <code>
     *          array(
     *              array('product' => 'PRODUCTID1', 'quantity' => 1, 'price' => 59.90),
     *              array('product' => 'PRODUCTID2', 'quantity' => 2, 'price' => 10.0)
     *          )
     *      </code>
     * @param $orderId string
     * @param $price number
     * @param $currency string
     */
    public function reportPurchase($products, $orderId, $price, $currency)
    {
        $script = "";
        $productsJson = json_encode($products);
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push([" . PHP_EOL;
            $script .= "'trackPurchase'," . PHP_EOL;
            $script .= $price . "," . PHP_EOL;
            $script .= "'" . $currency . "'," . PHP_EOL;
            $script .= $productsJson . "," . PHP_EOL;
            $script .= $orderId . "" . PHP_EOL;
            $script .= "]);" . PHP_EOL;
        }

        return $script;
    }

    /**
     * @param $script
     */
    public function addScript($script)
    {
        self::$SCRIPTS[] = $script;
    }

    /**
     * @return array
     */
    public function getScripts()
    {
        return self::$SCRIPTS;
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     * @param $text
     * @return mixed|null|string
     */
    public function sanitizeFieldName($text)
    {
        $maxLength = 50;
        $delimiter = "_";

        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', $delimiter, $text);

        // trim
        $text = trim($text, $delimiter);

        // transliterate
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^_\w]+~', '', $text);

        if (empty($text)) {
            return null;
        }

        // max $maxLength (50) chars
        $text = substr($text, 0, $maxLength);
        $text = trim($text, $delimiter);

        return $text;
    }

    /**
     * @param $params
     * @return \stdClass
     */
    public function getFiltersValues($params)
    {
        $filters = new \stdClass();
        if (isset($params['cat'])) {

            $filters->filter_hc_category = '';
            $category = $this->catalogCategory->load($params['cat']);
            $categories = explode('/', $category->getPath());
            foreach ($categories as $cat) {
                $name = $category = $this->catalogCategory->load($cat)->getName();
                if (strpos($name, '/') !== false) {
                    $name = str_replace('/', '\/', $name);
                }
                $filters->filter_hc_category .= '/' . $name;

            }
            unset($params['cat']);
        }

        if (isset($params['price'])) {
            $prices = explode('-', $params['price']);
            if (!empty($prices[0])) {
                $filters->filter_from_incl_price = $prices[0];
            }
            if (!empty($prices[1])) {
                $filters->filter_to_incl_price = $prices[1];
            }
            unset($params['price']);
        }

        if (isset($params)) {
            $list = $this->factory->create();
            $list->load();
            foreach ($params as $param => $values) {
                $getAttribute = null;
                foreach($list as $cat) {
                    if($param == $cat->getName()) {
                        $getAttribute = $cat;
                    }
                }
                if ($getAttribute !== null) {
                    $values = html_entity_decode($values);
                    preg_match_all('!\d+!', $values, $matches);
                    if (is_array($matches[0])) {
                        $attrValues = [];
                        $paramName = 'filter_' . $param;
                        foreach ($matches[0] as $id) {
                            $attribute = $attribute = $getAttribute->getSource()->getOptionText($id);
                            $attrValues[] = $attribute;
                        }
                        $filters->paramName = $attrValues;
                    }
                }
            }
        }
        return $filters;
    }

    /**
     * @return array
     */
    public function getWidgetConfig($widgetName)
    {
        if(!isset($this->bxConfig['bxRecommendations'])){
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        $widgetConfig = [];
        if(isset($this->bxConfig['bxRecommendations']['others'])) {
            $widgetNames = explode(',', $this->bxConfig['bxRecommendations']['others']['widget']);
            $widgetScenarios = explode(',', $this->bxConfig['bxRecommendations']['others']['scenario']);
            $widgetMin = explode(',', $this->bxConfig['bxRecommendations']['others']['min']);
            $widgetMax = explode(',', $this->bxConfig['bxRecommendations']['others']['max']);
            $index =  array_search($widgetName, $widgetNames);

            if($index !== false){
                $widgetConfig = array('widget' => $widgetNames[$index], 'scenario' => $widgetScenarios[$index],
                    'min' => $widgetMin[$index], 'max' => $widgetMax[$index]);
            }
        }
        return $widgetConfig;
    }

    public function getCmsRecommendationBlocks($content)
    {
        $config = $this->config->getValue('bxRecommendations/blog',$this->scopeStore);
        $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'read';

        $recs = [];
        $recs[] = array(
            'widget'=>$choiceId,
            'scenario'=>'blog',
            'min'=>$config['min'],
            'max'=>$config['max']
        );

        return $recs;
    }

    public function getBlogRecommendationChoiceId()
    {
        $choice_id = $this->config->getValue('bxRecommendations/blog/widget', $this->scopeStore);
        return is_null($choice_id) ? 'read' : $choice_id;
    }

    public function getBlogReturnFields()
    {
        $fields = array(
            'title',
            $this->getExcerptFieldName(),
            $this->getLinkFieldName(),
            $this->getMediaUrlFieldName(),
            $this->getDateFieldName()
        );
        $extraFields = $this->getExtraFieldNames();

        return array_merge($fields, $extraFields);
    }

    public function getExcerptFieldName()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['excerptFieldName'])) {
            return $config['excerptFieldName'];
        }
        return 'products_blog_excerpt';
    }

    public function getLinkFieldName()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['linkFieldName'])) {
            return $config['linkFieldName'];
        }

        return 'products_blog_link';
    }

    public function getMediaUrlFieldName()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['mediaUrlFieldName'])) {
            return $config['mediaUrlFieldName'];
        }

        return 'products_blog_featured_media_url';
    }

    public function getDateFieldName()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['dateFieldName'])) {
            return $config['dateFieldName'];
        }

        return 'products_blog_date';
    }

    public function getExtraFieldNames()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['extraFieldNamesFieldName'])) {
            return explode(',', $config['extraFieldNamesFieldName']);
        }

        return [];
    }

    public function getBlogArticleImageWidth()
    {
        $config = $this->config->getValue('bxBlog/field',$this->scopeStore);
        if (isset($config['blogArticleImageHeight'])) {
            return explode(',', $config['blogArticleImageHeight']);
        }

        return 960;
    }

    public function getBlogArticleImageHeight()
    {
        $config = $this->config->getValue('bxRecommendations/field',$this->scopeStore);
        if (isset($config['getBlogArticleImageWidth'])) {
            return explode(',', $config['getBlogArticleImageWidth']);
        }

        return 580;
    }

    /**
     * @return bool
     */
    public function isPluginEnabled()
    {
        if(!isset($this->bxConfig['bxGeneral'])) {
            $this->bxConfig['bxGeneral'] = $this->config->getValue('bxGeneral', $this->scopeStore);
        }
        return (bool) ($this->bxConfig['bxGeneral']['general']['enabled'] && !$this->fallback);
    }

    /**
     * @return bool
     */
    public function isSearchEnabled()
    {
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxSearch']['search']['enabled']);
    }

    /**
     * @return bool
     */
    public function isBlogEnabled()
    {
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxSearch']['search']['blog']);
    }

    /**
     * @return bool
     */
    public function isAutocompleteEnabled()
    {
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isSearchEnabled() && $this->bxConfig['bxSearch']['autocomplete']['enabled']);
    }

    /**
     * @return bool
     */
    public function isTrackerEnabled()
    {
        if(!isset($this->bxConfig['bxGeneral'])) {
            $this->bxConfig['bxGeneral'] = $this->config->getValue('bxGeneral', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxGeneral']['tracker']['enabled']);
    }

    /**
     * @return bool
     */
    public function isCrosssellEnabled()
    {
        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['cart']['status']);
    }

    /**
     * @return bool
     */
    public function isRelatedEnabled()
    {
        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['related']['status']);
    }

    /**
     * @return bool
     */
    public function isUpsellEnabled()
    {
        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['upsell']['status']);
    }

    /**
     * @return bool
     */
    public function isBlogRecommendationEnabled()
    {
        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['blog']['enabled']);
    }

    /**
     * @return bool
     */
    public function isBannerEnabled()
    {
        if(!isset($this->bxConfig['bxBanner'])) {
            $this->bxConfig['bxBanner'] = $this->config->getValue('bxBanner', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxBanner']['banner']['status']);
    }

    /**
     * @return bool
     */
    public function isNavigationEnabled()
    {
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxSearch']['navigation']['enabled']);
    }

    /**
     * @return bool
     */
    public function isOverlayEnabled()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxOverlay']['overlay']['enabled']);
    }

    public function getOverlayHitcount()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return $this->bxConfig['bxOverlay']['overlay']['hitcount'];
    }

    public function getOverlayBannerChoiceHitCount()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return $this->bxConfig['bxOverlay']['overlay']['bannerHitcount'];
    }

    public function getOverlayOrder()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return $this->bxConfig['bxOverlay']['overlay']['order'];
    }

    public function getOverlayDir()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return $this->bxConfig['bxOverlay']['overlay']['dir'];
    }

    public function getOverlayPageOffset()
    {
        if(!isset($this->bxConfig['bxOverlay'])){
            $this->bxConfig['bxOverlay'] = $this->config->getValue('bxOverlay', $this->scopeStore);
        }
        return $this->bxConfig['bxOverlay']['overlay']['pageoffset'];
    }

    /**
     * @param $layer
     * @return bool
     */
    public function isEnabledOnLayer($layer)
    {
        switch(get_class($layer)){
            case 'Magento\Catalog\Model\Layer\Category\Interceptor':
                return $this->isNavigationEnabled();
            case 'Magento\Catalog\Model\Layer\Search\Interceptor':
                return $this->isSearchEnabled();
            case 'Magento\Catalog\Model\Layer\Category':
                return $this->isNavigationEnabled();
            case 'Magento\Catalog\Model\Layer\Search':
                return $this->isSearchEnabled();
            default:
                return false;
        }
    }

    /**
     * @param $productCollection
     * @param $ids
     * @return mixed
     */
    public function prepareProductCollection($productCollection, $ids)
    {
        $productCollection->addFieldToFilter('entity_id', $ids)->getSelect()
            ->order(new \Zend_Db_Expr('FIELD(e.entity_id,' . implode(',', $ids).')'));
        return $productCollection;
    }

    /**
     * @return mixed
     */
    public function getSubPhrasesLimit()
    {
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return $this->bxConfig['bxSearch']['advanced']['search_sub_phrases_limit'];
    }

    public function getFilterProductAttributes($context = 'search')
    {
        $attributes = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeId = $objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
        $attributeCollection = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection')
            ->setOrder('position','ASC')->load();

        $allowedTypes = array('multiselect', 'price', 'select');
        foreach($attributeCollection as $attribute) {
            $data = $attribute->getData();
            if (!in_array($data['frontend_input'], $allowedTypes)) {
                continue;
            }
            $position = $data['position'];
            $code = $data['attribute_code'];
            $type = 'list';

            if ($code == 'price') {
                $type = 'ranged';
            }
            if($context == 'search') {
                $addToRequest = (boolean) $data['is_filterable_in_search'];
            } else {
                $addToRequest = (boolean) $data['is_filterable'];
            }
            $code = $code == 'price' ? 'discountedPrice' : $this->getProductAttributePrefix() . $code;
            $attributes[$code] = array(
                'label' => $attribute->getStoreLabel($storeId),
                'type' => $type,
                'order' => 0,
                'position' => $position,
                'addToRequest' => $addToRequest
            );
        }
        uasort($attributes, function($a, $b){
            if($a['position'] == $b['position']){
                return strcmp($a['label'],$b['label']);
            }
            if($b['position'] == -1){
                return true;
            }
            return $a['position'] - $b['position'];
        });
        return $attributes;
    }

    /**
     * @return string
     */
    private function getProductAttributePrefix(){
        return 'products_';
    }

    /**
     * @return boolean
     */
    public function isSetup(){
        return $this->setup;
    }

    /**
     * @param $fallback
     */
    public function setFallback($fallback){
        $this->fallback = $fallback;
    }

    /**
     * @return bool
     */
    public function getFallback(){
        return $this->fallback;
    }

    /**
     * @param boolean $setup
     */
    public function setSetup($setup){
        $this->setup = $setup;
    }

    /**
     * @param $block
     */
    public function setCmsBlock($block){
        $this->cmsBlock = $block;
    }

    /**
     * @return array
     */
    public function getCmsBlock(){

        return $this->cmsBlock;
    }

    public function setRemoveParams($key) {
        $this->removeParams[] = $key;
    }

    public function getRemoveParams() {
        return $this->removeParams;
    }

    public function setSystemParams($key, $values) {
        $this->systemParams[$key] = $values;
    }

    public function getSystemParams() {
        return $this->systemParams;
    }

    public function getSeparator() {
        $separator = $this->config->getValue('bxSearch/advanced/parameter_separator', $this->scopeStore);
        if($separator == '') {
            $separator = ',';
        }
        return $separator;
    }

    public function getFacetOptions() {
        $fields = explode(',',$this->config->getValue('bxSearch/advanced/multiselect_fields', $this->scopeStore));
        $facetOptions = [];
        foreach ($fields as $field) {
            $values = explode(';', $field);
            $fieldName = $values[0];
            $andSelectedValues = sizeof($values) > 1 ? (bool)$values[1] : false;
            $facetOptions[$fieldName] = array('andSelectedValues' => $andSelectedValues);
        }
        return $facetOptions;
    }

    public function getExtraSortFields() {
        $sortRules = explode(';', $this->config->getValue('bxSearch/advanced/extra_sort_fields', $this->scopeStore));
        $sortFields = [];
        foreach ($sortRules as $rule) {
            if(empty($rule)){continue;}
            $fieldRuleMapping = explode(':', $rule);
            $additionalFields = explode(',',$fieldRuleMapping[1]);
            $sortFields[$fieldRuleMapping[0]] = [];
            foreach($additionalFields as $extraSortSelection)
            {
                $divided = explode('-', $extraSortSelection);
                $sortFields[$fieldRuleMapping[0]][$divided[0]] = $divided[1];
            }
        }

        return $sortFields;
    }

    /**
     * @param $array
     * @return array
     */
    public function useValuesAsKeys($array){
        return array_combine(array_keys(array_flip($array)),$array);
    }

    /**
     * @return mixed|string
     */
    public function notificationTrace() {
        $e = new \Exception();
        $trace = $e->getTraceAsString();
        return $trace;
    }

    public function getIsFinder() {
        return $this->isFinder;
    }

    public function setIsFinder($isFinder) {
        $this->isFinder = $isFinder;
    }

}
