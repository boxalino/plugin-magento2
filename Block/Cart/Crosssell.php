<?php
namespace Boxalino\Intelligence\Block\Cart;
use Magento\Checkout\Block\Cart\Crosssell as Mage_Crosssell;

/**
 * Class Crosssell
 * @package Boxalino\Intelligence\Block\Cart
 */
class Crosssell extends Mage_Crosssell{

    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory
     */
    protected $factory;

    /**
     * Crosssell constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     * @param \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        array $data=[]
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->p13nHelper = $p13nHelper;
        $this->factory = $factory;
        parent::__construct($context, $checkoutSession, $productVisibility, $productLinkFactory, $itemRelationsList, $stockHelper, $data);
        $this->getItems(false);
    }

    /**
     * @param bool $execute
     * @return $this|array|null
     */
    public function getItems($execute = true){
        if($this->bxHelperData->isCrosssellEnabled() && $this->bxHelperData->isPluginEnabled()){
            $config = $this->_scopeConfig->getValue('bxRecommendations/cart',$this->scopeStore);
            $products = [];
            foreach ($this->getQuote()->getAllItems() as $item) {
                $product = $item->getProduct();
                if ($product) {
                    $products[] = $product;
                }
            }

            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'basket';
            try{
                $entity_ids = $this->p13nHelper->getRecommendation(
                    $choiceId,
                    $products,
                    'basket',
                    $config['min'],
                    $config['max'],
                    $execute
                );
            }catch(\Exception $e){
                $this->bxHelperData->setFallback(true);
                $this->_logger->critical($e);
                return parent::getItems();
            }

            if(!$execute){
                return null;
            }

            if (empty($entity_ids)) {
                $entity_ids = [0];
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

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        if($this->bxHelperData->isCrosssellEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestUuid();
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getRequestGroupBy()
    {
        if($this->bxHelperData->isCrosssellEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestGroupBy();
        }

        return null;
    }

}
