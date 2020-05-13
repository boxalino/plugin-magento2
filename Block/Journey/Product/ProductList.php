<?php
namespace Boxalino\Intelligence\Block\Journey\Product;

use \Boxalino\Intelligence\Block\Journey\CPOJourney as CPOJourney;
use Boxalino\Intelligence\Block\Journey\General;

/**
 * Class ProductList
 * @package Boxalino\Intelligence\Block\Journey\Product
 */
class ProductList extends General implements CPOJourney
{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Boxalino\Intelligence\Helper\ResourceManager
     */
    public $bxResourceManager;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ProductList constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Block\BxJourney $journey
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\ResourceManager $bxResourceManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    )
    {
        parent::__construct($context, $journey, $p13nHelper,$data);
        $this->bxHelperData = $bxHelperData;
        $this->bxResourceManager = $bxResourceManager;
        $this->objectManager = $objectManager;
        $this->prepareCollection();
    }



    public function prepareCollection()
    {
        $variant_index = $this->getVariantIndex();
        $collection = $this->bxResourceManager->getResource($variant_index, 'collection');
        if(is_null($collection)) {
            $collection = $this->createCollection($variant_index);
            $this->bxResourceManager->setResource($collection, $variant_index, 'collection');
        }
    }

    public function createCollection($variant_index)
    {
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

    /**
     * Setting properties for the product element in the list
     *
     * @param $visualElement
     * @param $index int
     * @return array
     */
    public function getAdditionalParameters($visualElement, $index)
    {
        if($this->checkVisualElementParam($visualElement, 'format', 'product'))
        {
            return [
                'bx_id' => $this->getProductId($visualElement, $index),
                'bx_collection_id' => $this->getCollectionId(),
                'bx_index' => $index
            ];
        }

        return [];
    }

    /**
     * Used in the template in order to access the product ID from the once-loaded collection
     *
     * @param $visualElement
     * @param $index
     * @return |null
     */
    public function getProductId($visualElement, $index)
    {
        $id = null;
        foreach ($visualElement['parameters'] as $parameter)
        {
            if($parameter['name'] == 'product_id') {
                $id = reset($parameter['values']);
                break;
            }
        }

        if(!$id)
        {
            $ids = $this->p13nHelper->getEntitiesIds($this->getVariantIndex());
            $id = isset($ids[$index]) ? $ids[$index] : null;
        }

        return $id;
    }

    public function getCollectionId()
    {
        return $this->getVariantIndex();
    }

    public function getVariantIndex()
    {
        $visualElement = $this->getData('bxVisualElement');
        $variant_index = 0;
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == 'variant') {
                $variant_index = reset($parameter['values']);
                break;
            }
        }
        return $variant_index;
    }

    public function checkVisualElementParam($visualElement, $key, $value)
    {
        $parameters = $visualElement['parameters'];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == $key){
                if(in_array($value, $parameter['values'])) {
                    return true;
                }
            }
        }
        return false;
    }

}
