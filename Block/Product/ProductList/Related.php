<?php
namespace Boxalino\Intelligence\Block\Product\ProductList;
use Magento\Catalog\Block\Product\ProductList\Related as MageRelated;

/**
 * Class Related
 * @package Boxalino\Intelligence\Block\Product\ProductList
 */
class Related extends MageRelated{

    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory
     */
    protected $factory;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * Related constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart
     * @param \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        array $data = []
    )
    {
        $this->bxHelperData = $bxHelperData;
        $this->factory = $factory;
        $this->p13nHelper = $p13nHelper;
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
        $this->_prepareData(false);
    }

    /**
     * @return $this
     */
    protected function _prepareData($execute = true)
    {
        if($this->bxHelperData->isRelatedEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            $product = $this->_coreRegistry->registry('product');
            $config = $this->_scopeConfig->getValue('bxRecommendations/related',$this->scopeStore);
            $choiceId = (isset($config['widget']) && $config['widget'] != "") ? $config['widget'] : 'similar';
            $entity_ids = [];

            try{
                if(!is_null($product)) {
                    $entity_ids = $this->p13nHelper->getRecommendation(
                        $choiceId,
                        $product,
                        'product',
                        $config['min'],
                        $config['max'],
                        $execute
                    );
                }
            } catch(\Exception $e) {
                $this->bxHelperData->setFallback(true);
                $this->_logger->critical($e);
                return parent::_prepareData();
            }

            if(!$execute){
                return null;
            }

            if (empty($entity_ids)) {
                $entity_ids = [0];
            }
            $this->_itemCollection = $this->factory->create();
            $this->_itemCollection = $this->bxHelperData->prepareProductCollection($this->_itemCollection, $entity_ids)
                ->addAttributeToSelect('*');

            if ($this->moduleManager->isEnabled('Magento_Checkout')) {
                $this->_addProductAttributesAndPrices($this->_itemCollection);
            }
            $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
            $this->_itemCollection->load();

            foreach ($this->_itemCollection as $product) {
                $product->setDoNotUseCategoryId(true);
            }
            return $this;
        }

        return parent::_prepareData();
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        if($this->bxHelperData->isRelatedEnabled() && $this->bxHelperData->isPluginEnabled())
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
        if($this->bxHelperData->isRelatedEnabled() && $this->bxHelperData->isPluginEnabled())
        {
            return $this->p13nHelper->getRequestGroupBy();
        }

        return null;
    }

}
