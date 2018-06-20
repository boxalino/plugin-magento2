<?php

namespace Boxalino\Intelligence\Block\Journey\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;

/**
 * Class ProductView
 * @package Boxalino\Intelligence\Block\Journey\Product
 */
class ProductView extends ListProduct implements CPOJourney{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var null
     */
    protected $_product = null;

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    protected $bxResourceManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ProductView constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Boxalino\Intelligence\Block\BxJourney $journey
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    )
    {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxJourney = $journey;
        $this->_logger = $context->getLogger();
        $this->bxResourceManager = $bxResourceManager;
        $this->objectManager = $objectManager;
    }

    public function getSubRenderings()
    {
        $elements = array();
        $element = $this->getData('bxVisualElement');
        if(isset($element['subRenderings'][0]['rendering']['visualElements'])) {
            $elements = $element['subRenderings'][0]['rendering']['visualElements'];
        }
        return $elements;
    }

    public function renderVisualElement($element, $additional_parameter = null)
    {
        return $this->bxJourney->createVisualElement($element, $additional_parameter)->toHtml();
    }

    protected function loadProduct($product_id) {
        $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
        return $product;
    }

    public function getElementIndex() {
        return $this->getData('bx_index');
    }

    protected function bxGetProduct() {
        $visualElement = $this->getData('bxVisualElement');
        $product = false;
        $variant_index = 0;
        $index = $this->getElementIndex();
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
            } else if($parameter['name'] == 'index') {
                $index = reset($parameter['values']);
            }
        }

        $ids = $this->p13nHelper->getEntitiesIds(null, $variant_index);
        $entity_id = isset($ids[$index]) ? $ids[$index] : null;
        if($entity_id) {
            $collection = $this->bxResourceManager->getResource($variant_index, 'collection');
            if(!is_null($collection)) {
                foreach ($collection as $product) {
                    if($product->getId() == $entity_id){
                        return $product;
                    }
                }
            }

            $product = $this->bxResourceManager->getResource($entity_id, 'product');
            if(is_null($product)) {
                $product = $this->loadProduct($entity_id);
                $this->bxResourceManager->setResource($product, $entity_id, 'product');
            }
        }
        return $product;
    }

    public function bxProduct() {
        if(is_null($this->_product)) {
            $this->_product = $this->bxGetProduct();
        }
        return $this->_product;
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getResponse()->getLocalizedValue($values);
    }

    protected function _beforeToHtml()
    {
        return $this;
    }
}
