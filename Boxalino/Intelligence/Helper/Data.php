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
    protected static $SCRIPTS = array();

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
     * @return array
     */
    public function getBasketItems()
    {
        $items = array();
        $checkout = $this->checkoutSession;
        $quote = $checkout->getQuote();
        if ($quote) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $items[] = $item->product_id;
            }
        }
        return $items;
    }

    /**
     * @param $term
     * @param null $filters
     * @return string
     */
    public function reportSearch($term, $filters = null)
    {
        if ($this->isTrackerEnabled()) {
            $logTerm = addslashes($term);
            $script = "_bxq.push(['trackSearch', '" . $logTerm . "', " . json_encode($filters) . "]);" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    /**
     * @param $product
     * @return string
     */
    public function reportProductView($product)
    {
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackProductView', '" . $product . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
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
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);" . PHP_EOL;
            print_r($script);
            return $script;
        } else {
            return '';
        }
    }

    /**
     * @param $categoryID
     * @return string
     */
    public function reportCategoryView($categoryID)
    {
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackCategoryView', '" . $categoryID . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    /**
     * @param $customerId
     * @return string
     */
    public function reportLogin($customerId)
    {
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackLogin', '" . $customerId . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
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
        } else {
            return '';
        }
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
     * @return bool
     */
    public function isPluginEnabled(){
        return (bool)$this->config->getValue('bxGeneral/general/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isSearchEnabled(){
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxSearch/search/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isAutocompleteEnabled(){
        return (bool)$this->isSearchEnabled() && $this->config->getValue('bxSearch/autocomplete/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isTrackerEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxGeneral/tracker/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isCrosssellEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/cart/status', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isRelatedEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/related/status', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isUpsellEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/upsell/status', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isNavigationEnabled()
    {
        return (bool)$this->isSearchEnabled() && $this->config->getValue('bxSearch/navigation/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isLeftFilterEnabled(){
        return (bool)$this->isSearchEnabled() && $this->config->getValue('bxSearch/left_facets/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isTopFilterEnabled(){
        return (bool)$this->isSearchEnabled() && $this->config->getValue('bxSearch/top_facet/enabled', $this->scopeStore);
    }

    /**
     * @return bool
     */
    public function isFilterLayoutEnabled(){
        return (bool)$this->isSearchEnabled() && $this->config->getValue('bxSearch/filter/enabled', $this->scopeStore);
    }

    /**
     * @return int
     */
    public function getCategoriesSortOrder(){
        $fields = explode(',', $this->config->getValue('bxSearch/left_facets/fields',$this->scopeStore));
        $orders = explode(',', $this->config->getValue('bxSearch/left_facets/orders',$this->scopeStore));
        foreach($fields as $index => $field){
            if($field == 'categories'){
                return (int)$orders[$index];
            }
        }
        return 0;
    }
}
