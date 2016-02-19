<?php
namespace Boxalino\Frontend\Block\Cart;
use Magento\Checkout\Block\Cart\Crosssell as Mage_Crosssell;

class Crosssell extends Mage_Crosssell
{

    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $p13nHelper;
    protected $bxHelperData;
    protected $factory;
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Frontend\Helper\Data $bxHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        array $data=[]
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
        $this->factory = $factory;
        parent::__construct($context, $checkoutSession, $productVisibility, $productLinkFactory, $itemRelationsList, $stockHelper, $data);
    }

    /**
     * Get crosssell items
     *
     * @return array
     */
    public function getItems()
    {
        if($this->bxHelperData->isCrosssellEnabled()){
            $config = $this->_scopeConfig->getValue('bxRecommendations/cart',$this->scopeStore);

            if(!$config['enabled']){
                return parent::getItems();
            }

            $products = array();
            foreach ($this->getQuote()->getAllItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $products[] = $product;
                }
            }

            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'basket';

            $recommendations = $this->p13nHelper->getRecommendation(
                'basket',
                $choiceId,
                $config['min'],
                $config['max'],
                $products
            );

            $entity_ids = array_keys($recommendations);

            if (empty($entity_ids)) {
                return parent::getItems();
            }

            $items = $this->factory->create()
                ->addFieldToFilter('entity_id', $entity_ids)->addAttributeToSelect('*');

            $items->load();

            foreach ($items as $product) {
                $product->setDoNotUseCategoryId(true);
            }

            return $items;
        }
       return parent::getItems();
    }

}
