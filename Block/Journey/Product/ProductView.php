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
     * View constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Boxalino\Intelligence\Block\BxJourney $journey
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
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
        array $data = []
    )
    {
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxJourney = $journey;
        $this->_logger = $context->getLogger();
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);
        return $product;
    }

    public function getElementIndex() {
        return $this->getData('bx_index');
    }

    protected function getProductIdForElement() {
        $visualElement = $this->getData('bxVisualElement');

        $choice_id = null;
        foreach ($visualElement['widgets'] as $widget) {
            $choice_id = $widget['widget'];
            break;
        }
        $ids = $this->p13nHelper->getEntitiesIds($choice_id);
        $index = isset($config['index']) ? $config['index'] : $this->getElementIndex();
        return isset($ids[$index]) ? $ids[$index] : null;
    }

    public function bxProduct() {
        if(is_null($this->_product) && $product_id = $this->getProductIdForElement()) {
            $this->_product = $this->loadProduct($product_id);
        }
        return $this->_product;
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getResponse()->getLocalizedValue($values);
    }
}
