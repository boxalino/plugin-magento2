<?php
namespace Boxalino\Frontend\Helper;
use Boxalino\Frontend\Lib\vendor\HttpP13n;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $reader;
    protected $checkoutSession;
    protected $customerSession;
    protected $config;
    protected $catalogProduct;
    protected $factory;
    protected $catalogCategory;
    protected $catalogSearch;
    protected $controllerInterface;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    
    public function __construct(
        \Magento\CatalogSearch\Helper\Data $catalogSearch,
        \Magento\Framework\App\Helper\Context $context,
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
        spl_autoload_register(array('\Boxalino\Frontend\Helper\Data', '__loadClass'), TRUE, TRUE);
        parent::__construct($context);
    }

    public static function __loadClass($name)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $reader = $om->get('Magento\Framework\Module\Dir\Reader');
        if (strpos($name, 'Thrift\\') !== false) {
            try {
                $file = '/'.str_replace('Boxalino/Frontend/', '', str_replace('\\', '/', $name)) . '.php';
                if(strpos($file, 'Lib/vendor')===false) {
                    $file = "/Lib/vendor" . $file;
                }
                include_once($reader->getModuleDir('', 'Boxalino_Frontend') . $file);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function getBasketAmount()
    {
        $checkout = $this->checkoutSession;
        $quote = $checkout->getQuote();
        $amount = 0;
        if ($quote) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $amount += $item->getQty() * $item->getPrice();
            }
        }
        return $amount;
    }

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

    public function getBasketContent()
    {
        $checkout = $this->checkoutSession;
        $quote = $checkout->getQuote();
        $items = array();
        if ($quote) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $items[] = array(
                    'id' => $item->product_id,
                    'name' => $item->getProduct()->getName(),
                    'quantity' => $item->getQty(),
                    'price' => $item->getPrice(),
                    'widget' => $this->isProductFacilitated($item->product_id)
                );
            }
        }
        return @json_encode($items);
    }

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

    public function reportProductView($product)
    {
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackProductView', '" . $product . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

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

    public function reportCategoryView($categoryID)
    {
        if ($this->isTrackerEnabled()) {
            $script = "_bxq.push(['trackCategoryView', '" . $categoryID . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

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

    protected static $SCRIPTS = array();
    public function addScript($script) {
        self::$SCRIPTS[] = $script;
    }

    public function getScripts() {
        return self::$SCRIPTS;
    }

    public function getLoggedInUserId()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerData = $this->customerSession->getCustomer();
            return $customerData->getId();
        } else {
            return null;
        }
    }

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
    public function isPluginEnabled(){
        return (bool)$this->config->getValue('bxGeneral/general/enabled', $this->scopeStore);
    }

    public function isSearchEnabled(){
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxSearch/search/enabled', $this->scopeStore);
    }

    public function isAutocompleteEnabled(){
        return (bool)$this->isPluginEnabled() && $this->isSearchEnabled() && $this->config->getValue('bxSearch/autocomplete/enabled', $this->scopeStore);
    }

    public function isTrackerEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxGeneral/tracker/enabled', $this->scopeStore);
    }

    public function isCrosssellEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/cart/status', $this->scopeStore);
    }

    public function isRelatedEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/related/status', $this->scopeStore);
    }

    public function isUpsellEnabled()
    {
        return (bool)$this->isPluginEnabled() && $this->config->getValue('bxRecommendations/upsell/status', $this->scopeStore);
    }

}
