<?php
namespace Boxalino\Intelligence\Block\Journey\Product;

use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;

/**
 * Linked to GENERAL class for interface functions
 * Class ProductListToolbar
 * @package Boxalino\Intelligence\Block\Journey\Product
 */
class ProductListToolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar implements CPOJourney
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    protected $bxResourceManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ProductListToolbar constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param ToolbarModel $toolbarModel
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Catalog\Helper\Product\ProductList $productListHelper
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Boxalino\Intelligence\Block\BxJourney $journey
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Catalog\Helper\Product\ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = [])
    {
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
        $this->_logger = $context->getLogger();
        $this->bxJourney = $journey;
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        $this->bxResourceManager = $bxResourceManager;
        $this->objectManager = $objectManager;
        $this->prepareCollection();
    }

    protected function prepareCollection() {
        $visualElement = $this->getData('bxVisualElement');
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        $collection = $this->bxResourceManager->getResource($variant_index, 'collection');
        if(is_null($collection)) {
            $collection = $this->createCollection($variant_index);
            $this->bxResourceManager->setResource($collection, $variant_index, 'collection');
        }
        $this->setCollection($collection);
    }

    protected function createCollection($variant_index) {
        $entity_ids = $this->p13nHelper->getEntitiesIds($variant_index);

        $collection = $this->objectManager->create('\Boxalino\\Intelligence\\Model\\Collection');
        $collection = $this->bxHelperData->prepareProductCollection($collection, $entity_ids);
        $collection->setStoreId($this->_storeManager->getStore()->getId())->addAttributeToSelect('*');
        $collection->load();

        $page = is_null($this->getRequest()->getParam('p')) ? 1 : $this->getRequest()->getParam('p');
        $collection->setCurBxPage($page);
        $limit = $this->getRequest()->getParam('product_list_limit') ? $this->getRequest()->getParam('product_list_limit') : $this->p13nHelper->getMagentoStoreConfigPageSize();
        $totalHitCount = $this->p13nHelper->getTotalHitCount($variant_index);
        $lastPage = ceil($totalHitCount /$limit);
        $collection->setLastBxPage($lastPage);
        $collection->setBxTotal($totalHitCount);
        return $collection;
    }

    public function getSubRenderings(){
        $elements = [];
        $element = $this->getData('bxVisualElement');
        if(isset($element['subRenderings'][0]['rendering']['visualElements'])) {
            $elements = $element['subRenderings'][0]['rendering']['visualElements'];
        }
        return $elements;
    }

    public function renderVisualElement($element, $additional_parameter = null){
        return $this->bxJourney->createVisualElement($element, $additional_parameter)->toHtml();
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getResponse()->getLocalizedValue($values);
    }
}
