<?php
namespace Boxalino\Intelligence\Helper;
/**
 * Class Data
 * @package Boxalino\Intelligence\Helper
 */
class Data{

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
    protected static $SCRIPTS = array();

    /**
     * @var array
     */
    protected $bxConfig = array();

    /**
     * @var bool
     */
    protected $setup = true;

    /**
     * @var array
     */
    protected $cmsBlock = array();

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
    )
    {
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
    public function reportSearch($term, $filters = null){

        if ($this->isTrackerEnabled()) {
            $logTerm = addslashes($term);
            $script = "_bxq.push(['trackSearch', '" . $logTerm . "', " . json_encode($filters) . "]);" . PHP_EOL;
            return $script;
        }
        return '';
    }

    /**
     * @param $product
     * @return string
     */
    public function reportProductView($product){

        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackProductView', '" . $product . "'])" . PHP_EOL;
            return $script;
        }
        return '';
    }

    /**
     * @param $product
     * @param $count
     * @param $price
     * @param $currency
     * @return string
     */
    public function reportAddToBasket($product, $count, $price, $currency){

        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);" . PHP_EOL;
            print_r($script);
            return $script;
        }
        return '';
    }

    /**
     * @param $categoryID
     * @return string
     */
    public function reportCategoryView($categoryID){

        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackCategoryView', '" . $categoryID . "'])" . PHP_EOL;
            return $script;
        }
        return '';
    }

    /**
     * @param $customerId
     * @return string
     */
    public function reportLogin($customerId){

        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackLogin', '" . $customerId . "'])" . PHP_EOL;
            return $script;
        }
        return '';
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
    public function reportPurchase($products, $orderId, $price, $currency){

        $productsJson = json_encode($products);
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push([" . PHP_EOL;
            $script .= "'trackPurchase'," . PHP_EOL;
            $script .= $price . "," . PHP_EOL;
            $script .= "'" . $currency . "'," . PHP_EOL;
            $script .= $productsJson . "," . PHP_EOL;
            $script .= $orderId . "" . PHP_EOL;
            $script .= "]);" . PHP_EOL;
            return $script;
        }
        return '';
    }

    /**
     * @param $script
     */
    public function addScript($script) {
        self::$SCRIPTS[] = $script;
    }

    /**
     * @return array
     */
    public function getScripts() {
        return self::$SCRIPTS;
    }

    /**
     * @param $params
     * @return \stdClass
     */
    public function getFiltersValues($params){

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
                        $attrValues = array();
                        $paramName = 'filter_' . $param;
                        foreach ($matches[0] as $id) {
                            $attribute = $attribute = $getAttribute->getSource()->getOptionText($id);
                            $attrValues[] = $attribute;
                        }
                        $filters->$paramName = $attrValues;
                    }
                }
            }
        }
        return $filters;
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     * @param $text
     * @return mixed|null|string
     */
    public function sanitizeFieldName($text){

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
     * @param $fieldName
     * @return bool
     */
    public function isHierarchical($fieldName){

        $facetConfig = $this->config->getValue('bxSearch/left_facets', $this->scopeStore);
        $fields = explode(",", $facetConfig['fields']);
        $type = explode(",", $facetConfig['types']);

        if(in_array($fieldName,$fields )){
            if($type[array_search($fieldName, $fields)] == 'hierarchical'){
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getWidgetConfig($widgetName){

        if(!isset($this->bxConfig['bxRecommendations'])){
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }

        $widgetNames = explode(',', $this->bxConfig['bxRecommendations']['others']['widget']);
        $widgetScenarios = explode(',', $this->bxConfig['bxRecommendations']['others']['scenario']);
        $widgetMin = explode(',', $this->bxConfig['bxRecommendations']['others']['min']);
        $widgetMax = explode(',', $this->bxConfig['bxRecommendations']['others']['max']);

        $index =  array_search($widgetName, $widgetNames);
        $widgetConfig = array();
        if($index !== false){
            $widgetConfig = array('widget' => $widgetNames[$index], 'scenario' => $widgetScenarios[$index],
                'min' => $widgetMin[$index], 'max' => $widgetMax[$index]);
        }
        return $widgetConfig;
    }

    /**
     * @return bool
     */
    public function isPluginEnabled(){

        if(!isset($this->bxConfig['bxGeneral'])) {
            $this->bxConfig['bxGeneral'] = $this->config->getValue('bxGeneral', $this->scopeStore);
        }
        return (bool) $this->bxConfig['bxGeneral']['general']['enabled'];
    }

    /**
     * @return bool
     */
    public function isSearchEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxSearch']['search']['enabled']);
    }

    /**
     * @return bool
     */
    public function isAutocompleteEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isSearchEnabled() && $this->bxConfig['bxSearch']['autocomplete']['enabled']);
    }

    /**
     * @return bool
     */
    public function isTrackerEnabled(){

        if(!isset($this->bxConfig['bxGeneral'])) {
            $this->bxConfig['bxGeneral'] = $this->config->getValue('bxGeneral', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxGeneral']['tracker']['enabled']);
    }
    
    /**
     * @return bool
     */
    public function isCrosssellEnabled(){

        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['cart']['status']);
    }

    /**
     * @return bool
     */
    public function isRelatedEnabled(){

        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['related']['status']);
    }

    /**
     * @return bool
     */
    public function isUpsellEnabled(){

        if(!isset($this->bxConfig['bxRecommendations'])) {
            $this->bxConfig['bxRecommendations'] = $this->config->getValue('bxRecommendations', $this->scopeStore);
        }
        return (bool)($this->isPluginEnabled() && $this->bxConfig['bxRecommendations']['upsell']['status']);
    }

    /**
     * @return bool
     */
    public function isNavigationEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isSearchEnabled() && $this->bxConfig['bxSearch']['navigation']['enabled']);
    }

    /**
     * @return bool
     */
    public function isLeftFilterEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)$this->bxConfig['bxSearch']['left_facets']['enabled'];
    }

    /**
     * @return bool
     */
    public function isTopFilterEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)$this->bxConfig['bxSearch']['top_facet']['enabled'];
    }

    /**
     * @return bool
     */
    public function isFilterLayoutEnabled($category=true){

        if($category){
            $type = 'navigation';
        }else{
            $type = 'search';
        }
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)($this->isSearchEnabled() && $this->bxConfig['bxSearch'][$type]['filter']);
    }

    /**
     * @return bool
     */
    public function isSliderEnabled(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return (bool)(strpos($this->bxConfig['bxSearch']['left_facets']['fields'],'discountedPrice') !== false) && 
            $this->isLeftFilterEnabled();
    }

    /**
     * @return mixed
     */
    public function getSubPhrasesLimit(){
        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        return $this->bxConfig['bxSearch']['advanced']['search_sub_phrases_limit'];
    }

    /**
     * @return int
     */
    public function getCategoriesSortOrder(){

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }

        $fields = explode(',', $this->bxConfig['bxSearch']['left_facets']['fields']);
        $orders = explode(',', $this->bxConfig['bxSearch']['left_facets']['orders']);

        foreach($fields as $index => $field){
            if($field == 'categories'){
                return (int)$orders[$index];
            }
        }
        return 0;
    }

    /**
     * @return string
     */
    private function getProductAttributePrefix(){
        return 'products_';
    }
    
    /**
     * @return array
     */
    public function getFilterProductAttributes(){
        $attributes = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeId = $objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore()->getId();

        $attributeCollection = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection')
            ->addIsFilterableFilter()->addVisibleFilter()->setOrder('position','ASC')->load();
        $leftFacets = $this->getLeftFacets();

        $allowedTypes = array('multiselect', 'price', 'select');
        foreach($attributeCollection as $attribute){
            $data = $attribute->getData();
            if(!in_array($data['frontend_input'], $allowedTypes)){
                continue;
            }
            $position = $data['position'];
            $code = $data['attribute_code'];
            $type = 'list';
            
            if($code == 'price'){
                $type = 'ranged';
            }
            $code = $code == 'price' ? 'discountedPrice' : $this->getProductAttributePrefix() . $code;
            $attributes[$code] = array(
                'label' => $attribute->getStoreLabel($storeId),
                'type' => $type, 
                'order' => 0,
                'position' => $position
            );
        }
        
        $attributes = array_merge($attributes, $leftFacets);
        uasort($attributes, function($a, $b){
            if($a['position'] == $b['position']){
                return strcmp($a['label'],$b['label']);
            }
            return $a['position'] - $b['position'];
        });
        
        return $attributes;
    }

    public function getLeftFacetFieldNames(){
        return array_keys($this->getFilterProductAttributes());
    }
    /**
     * @return array
     * @throws \Exception
     */
    public function getLeftFacets() {

        if(!isset($this->bxConfig['bxSearch'])){
            $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
        }
        $fields = explode(',', $this->bxConfig['bxSearch']['left_facets']['fields']);
        $labels = explode(',', $this->bxConfig['bxSearch']['left_facets']['labels']);
        $types = explode(',', $this->bxConfig['bxSearch']['left_facets']['types']);
        $orders = explode(',', $this->bxConfig['bxSearch']['left_facets']['orders']);
        $position = explode(',', $this->bxConfig['bxSearch']['left_facets']['position']);

        if($fields[0] == "" || !$this->isLeftFilterEnabled()) {
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
        if(sizeof($fields) != sizeof($position)) {
            throw new \Exception("number of defined left facets fields doesn't match the number of defined left facet position: " . implode(',', $fields) . " versus " . implode(',', $position));
        }

        $facets = array();
        foreach($fields as $k => $field){
            $facets[$field] = array(
                'label' => $labels[$k],
                'type' =>$types[$k],
                'order' => $orders[$k],
                'position' => $position[$k]);
        }

        return $facets;
    }
    
    /**
     * @return array
     * @throws \Exception
     */
    public function getAllFacetFieldNames() {

        $allFacets = array_keys($this->getFilterProductAttributes());
        if($this->getTopFacetFieldName() != null) {
            $allFacets[] = $this->getTopFacetFieldName();
        }
        return $allFacets;
    }
    
    /**
     * @return array|null
     */
    public function getTopFacetValues() {

        if($this->isTopFilterEnabled()){

            if(!isset($this->bxConfig['bxSearch'])){
                $this->bxConfig['bxSearch'] = $this->config->getValue('bxSearch', $this->scopeStore);
            }
            $field = $this->bxConfig['bxSearch']['top_facet']['field'];
            $order = $this->bxConfig['bxSearch']['top_facet']['order'];
            return array($field, $order);
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getTopFacetFieldName()
    {
        list($topField, $topOrder) = $this->getTopFacetValues();
        return $topField;
    }
    
    /**
     * @return boolean
     */
    public function isSetup(){

        return $this->setup;
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
}
