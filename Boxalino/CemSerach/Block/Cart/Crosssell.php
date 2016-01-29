<?php
namespace Boxalino\CemSearch\Block\Cart;
use Magento\Checkout\Block\Cart\Crosssell;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Helper\Catalog;
use Magento\Checkout\Model\ResourceModel\Cart;
use Boxalino\CemSearch\Helper\P13n\Recommendation;
class Boxalino_CemSearch_Block_Cart_Crosssell extends Crosssell
{

    /**
     * Items quantity will be capped to this value
     *
     * @var int
     */
    protected $_maxItemCount = 4;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $itemCollection;
    protected $catalogHelper;
    protected $cart;
    protected $checkoutSession;
    protected $scopeConfig;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\Link $productLinkFactory,
        \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Collection $collection,
        Catalog $catalog,
        Cart $cart,
        array $data)
    {
        $this->scopeConfig = $scopeConfig;
        $this->itemCollection = $collection;
        $this->catalogHelper = $catalog;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $checkoutSession,
            $productVisibility, $productLinkFactory,
            $itemRelationsList, $stockHelper, $data);
    }

    /**
     * Get crosssell items
     *
     * @return array
     */
    public function getItems()
    {
        //$this->scopeConfig->getValue('Boxalino_General/general/enabled', $scopeStore)
        if (Mage::getStoreConfig('Boxalino_General/general/enabled') == 0) {
            return parent::getItems();
        }
        $name = Mage::getStoreConfig('Boxalino_Recommendation/cart/widget');
        #####################################################################################

        $cartItems = array();
        foreach ($this->getQuote()->getAllItems() as $item) {
            $productPrice = $item->getProductId()->getPrice();
            $productId = $item->getProductId();

            if ($item->getProductType() === 'configurable') {
                continue;
            }

            $cartItems[] = array('id' => $productId, 'price' => $productPrice);

        }

        $_REQUEST['basketContent'] = json_encode($cartItems);

        $p13nRecommendation = Recommendation::Instance();

        $response = $p13nRecommendation->getRecommendation('basket', $name);
        $entityIds = array();

        if ($response === null) {
            return null;
        }

        foreach ($response as $item) {
            $entityIds[] = $item[Mage::getStoreConfig('Boxalino_General/search/entity_id')];
        }

        if (empty($entityIds)) {
            return parent::getItems();
        }

        #########################################################################################

        $this->itemCollection->addFieldToFilter('entity_id', $entityIds)
            ->addAttributeToSelect('*');

        if ($this->catalogHelper->isModuleOutputEnabled('Mage_Checkout')) { //Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')
            $this->cart->addExcludeProductFilter($this->itemCollection,$this->checkoutSession->getQuoteId());
//            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($itemCollection,
//                Mage::getSingleton('checkout/session')->getQuoteId()

            $this->_addProductAttributesAndPrices($this->itemCollection);
        }
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->itemCollection);

        $this->itemCollection->load();
        $items = array();
        foreach ($this->itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
            $items[] = $product;
        }


        return $items;
    }

}
