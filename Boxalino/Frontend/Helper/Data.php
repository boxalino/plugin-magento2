<?php
namespace Boxalino\Frontend\Helper;
use Boxalino\Frontend\Helper\P13n\Config;
use Boxalino\Frontend\Helper\P13n\Sort;
use Boxalino\Frontend\Lib\vendor\HttpP13n;
use Magento\Search\Model\QueryFactory;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $reader;
    protected $checkoutSession;
    protected $customerSession;
    protected $config;
    protected $request;
    protected $registry;
    protected $catalogProduct;
    protected $catalogCategory;
    protected $catalogSearch;
    protected $controllerInterface;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $searchAdapter;
    protected $searchAdapterInitialized = false;
    private $_queryFactory;

    public function __construct(
        \Magento\CatalogSearch\Helper\Data $catalogSearch,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Category $catalogCategory,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Framework\App\FrontControllerInterface $controllerInterface,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Registry $registry,
        QueryFactory $queryFactory,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nAdapter
    )
    {
        $this->searchAdapter = $p13nAdapter;
        $this->registry = $registry;
        $this->request = $request;
        $this->controllerInterface = $controllerInterface;
        $this->catalogCategory = $catalogProduct;
        $this->config = $scopeConfig;
        $this->catalogCategory = $catalogCategory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->catalogSearch = $catalogSearch;
        $this->_queryFactory = $queryFactory;
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

    public function isSalesTrackingEnabled()
    {
        $trackSales = $this->config->getValue('Boxalino_General/tracker/analytics',$this->scopeStore); //Mage::getStoreConfig('Boxalino_General/tracker/analytics');
        return ($trackSales == 1);
    }

    public function isAnalyticsEnabled()
    {
        return (bool)$this->config->getValue('Boxalino_General/tracker/analytics', $this->scopeStore);
    }

    public function reportSearch($term, $filters = null)
    {
        if ($this->isAnalyticsEnabled()) {
            $logTerm = addslashes($term);
            $script = "_bxq.push(['trackSearch', '" . $logTerm . "', " . json_encode($filters) . "]);" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    public function reportProductView($product)
    {
        if ($this->isAnalyticsEnabled()) {
            $script = "_bxq.push(['trackProductView', '" . $product . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    public function reportAddToBasket($product, $count, $price, $currency)
    {
        if ($this->isAnalyticsEnabled()) {
            $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    public function reportCategoryView($categoryID)
    {
        if ($this->isAnalyticsEnabled()) {
            $script = "_bxq.push(['trackCategoryView', '" . $categoryID . "'])" . PHP_EOL;
            return $script;
        } else {
            return '';
        }
    }

    public function reportLogin($customerId)
    {
        if ($this->isAnalyticsEnabled()) {
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
        $trackSales = $this->config->getValue('Boxalino_General/tracker/track_sales',$this->scopeStore);

        $productsJson = json_encode($products);
        if ($trackSales == 1) {
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

    public function getLoggedInUserId()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerData = $this->customerSession->getCustomer();
            return $customerData->getId();
        } else {
            return null;
        }
    }

    public function getAccount()
    {
        $isDev = $this->config->getValue('Boxalino_General/general/account_dev',$this->scopeStore);
        $account = $this->config->getValue('Boxalino_General/general/di_account',$this->scopeStore);

        if ($isDev) {
            return $account . '_dev';
        }
        return $account;
    }

    public function getFiltersValues($params)
    {
        $filters = new stdClass();
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
            foreach ($params as $param => $values) {
                $getAttribute = $this->catalogProduct->getResource()->getAttribute($param);
                if ($getAttribute !== false) {
                    $values = html_entity_decode($values);
                    preg_match_all('!\d+!', $values, $matches);
                    if (is_array($matches[0])) {
                        $attrValues = array();
                        foreach ($matches[0] as $id) {
                            $paramName = 'filter_' . $param;
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

    public function getSearchAdapter()
    {

        if ($this->searchAdapterInitialized == false) {
            $storeConfig = $this->scopeConfig->getValue('Boxalino_General/general',$this->scopeStore);
            $request = $this->request;
            $p13nConfig = new Config(
                $storeConfig['host'],
                $this->getAccount(),
                $storeConfig['p13n_username'],
                $storeConfig['p13n_password'],
                $storeConfig['domain']
            );
            $p13nSort = new Sort();

            $field = '';
            $dir = '';
            $order = $request->getParam('order');
            if(isset($order)){
                if($order == 'name'){
                    $field = 'title';
                } elseif($order == 'price'){
                    $field = 'discountedPrice';
                }
            }
            $dirOrder = $request->getParam('dir');
            if($dirOrder){
                $dir = $dirOrder == 'asc' ? false : true;
            } else{
                $dir = false;
            }

            if($field !== '' && $dir !== ''){
                $p13nSort->push($field, $dir);
            }

            $this->searchAdapter->setConfig($p13nConfig);

            $categoryId = $request->getParam('bx_category_id');
            if (empty($categoryId)) {
                /* @var $category Mage_Catalog_Model_Category */
                $category = $this->registry->registry('current_category');
                if (!empty($category)) {
                    $_REQUEST['bx_category_id'][0] = $category->getId();
                }
                // GET param 'cat' may override the current_category,
                // i.e. when clicking on subcategories in a category page
                $cat = $request->getParam('cat');
                if (!empty($cat)) {
                    $_REQUEST['bx_category_id'][0] = $cat;
                }
            }

            $generalConfig = $this->scopeConfig->getValue('Boxalino_General/search',$this->scopeStore);
            $pageSize = (int) $request->getParam(
                'limit',
                $generalConfig['quick_search_limit'] == 0 ? 1000 : $generalConfig['quick_search_limit']
            );
            $offset = abs(((int) $request->getParam('p', 1)) - 1) * $pageSize;

            $query = $this->_queryFactory->get();
            //$query->setStoreId($this->_storeManager->getStore()->getId());

          //  $this->catalogSearch->;
            $this->searchAdapter->setupInquiry(
                empty($generalConfig['quick_search']) ? 'search' : $generalConfig['quick_search'],
                $query->getQueryText(),
                substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2),
                array($generalConfig['entity_id'], 'categories'),
                $p13nSort, $offset, $pageSize
            );

            $this->searchAdapter->search();
            $this->searchAdapter->prepareAdditionalDataFromP13n();
            $this->searchAdapterInitialized = true;
        }
        return $this->searchAdapter;
    }

    public function resetSearchAdapter()
    {
        $this->searchAdapterInitialized = false;
    }
}
