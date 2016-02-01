<?php
namespace \Boxalino\Frontend\Block\Product\List;
use Boxalino\Frontend\Helper\P13n\Boxalino_Frontend_Helper_P13n_Recommendation;
class Boxalino_Frontend_Block_Product_List_Recommendation extends \Magento\Catalog\Block\Product\ProductList\Related
{
    protected $_itemCollection;
    protected $_recommendationName = '';
    protected $_recommendationAmount = 3;
    protected $_recommendationContext = array();
    protected $_recommendationParameterValues = array();
    protected $scopeConfig;
    protected $collection;
    protected $registry;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Registry $registry,
        Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        array $data
    )
    {
        $this->collection = $collection;
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context,$checkoutCart,$catalogProductVisibility,$checkoutSession,$moduleManager,$data);
    }

    public function configure($name, $amount = 3, $context = array(), $parameterValues = array())
    {
        $this->_recommendationName = $name;
        $this->_recommendationAmount = $amount;
        $this->_recommendationContext = $context;
        $this->_recommendationParameterValues = $parameterValues;
    }

    protected function _prepareData()
    {
        if (
            $this->scopeConfig->getValue('Boxalino_General/general/enabled',$this->scopeStore) == 0 ||
            $this->scopeConfig->getValue('Boxalino_Recommendation/related/status',$this->scopeStore) == 0
        ) {
            return parent::_prepareData();
        }

        if (isset($this->_recommendationContext['id'])) {
            $_REQUEST['productId'] = $this->_recommendationContext['id'];
        } elseif (count($this->_recommendationContext)) {
            $_REQUEST['p13nRequestContext'] = $this->_recommendationContext;
            foreach($_REQUEST['p13nRequestContext'] as $k => $v) {
                if($v == '') {
                    switch($k) {
                        case 'id':
                            $_REQUEST['p13nRequestContext'][$k] = $this->registry->registry('product')->getId();
                            break;
                        case 'category':
                            $_REQUEST['p13nRequestContext'][$k] = $this->registry->registry('current_category');
                            break;
                        default:
                            if(isset($this->_recommendationParameterValues[$k])) {
                                $_REQUEST['p13nRequestContext'][$k] = $this->_recommendationParameterValues[$k];
                            }
                    }
                }
            }
        }

        $p13nRecommendation = Boxalino_Frontend_Helper_P13n_Recommendation::Instance();

        $response = $p13nRecommendation->getRecommendation(
            'free', $this->_recommendationName, $this->_recommendationAmount
        );
        $entityIds = array();

        if ($response === null) {
            $this->_itemCollection = new Varien_Data_Collection();
            return $this;
        }

        foreach ($response as $item) {
            $entityIds[] = $item[$this->scopeConfig->getValue('Boxalino_General/search/entity_id',$this->scopeStore)];
        }

        $this->_itemCollection = $this->collection->addFieldToFilter('catalog/product_collection')
            ->addFieldToFilter('entity_id', $entityIds)
            ->addAttributeToSelect('*');

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter(
                $this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);

        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

    protected function _beforeToHtml()
    {
        $this->_prepareData();
        return parent::_beforeToHtml();
    }

    public function getItems()
    {
        return $this->_itemCollection;
    }

    /**
     * Get tags array for saving cache
     *
     * @return array
     */
    public function getCacheTags()
    {
        return array_merge(parent::getCacheTags(), $this->getItemsTags($this->getItems()));
    }
}
