<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class Facets
 * @package Boxalino\Intelligence\Block
 */
class Facets extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    private $p13nHelper;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    private $layer;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Catalog\Block\Category\View
     */
    protected $categoryViewBlock;

    /**
     * Facets constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Block\Category\View $categoryViewBlock,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_logger = $context->getLogger();
        $this->categoryViewBlock = $categoryViewBlock;
        $this->p13nHelper = $p13nHelper;
        $this->layer = $layerResolver->get();
        $this->objectManager = $objectManager;
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * @return array
     */
    public function getTopFilters(){
        try{
            if($this->bxHelperData->isEnabledOnLayer($this->layer) && $this->bxHelperData->isPluginEnabled()){
                if($this->layer instanceof \Magento\Catalog\Model\Layer\Category){
                    if(!is_null($this->categoryViewBlock->getCurrentCategory()) && $this->categoryViewBlock->isContentMode()){
                        $this->bxHelperData->setFallback(true);
                        return [];
                    }
                }
                $facets = $this->p13nHelper->getFacets();
                $top_facets = $facets->getTopFacets();
                if($facets && sizeof($top_facets) > 0) {
                    $fieldName = $top_facets[0];
                    $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
                    $filter = $this->objectManager->create(
                        "Boxalino\Intelligence\Model\Attribute",
                        ['data' => ['attribute_model' => $attribute], 'layer' => $this->layer]
                    );
                    $filter->setFacets($facets);
                    $filter->setFieldName($fieldName);
                    return $filter->getItems();
                }
            }
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
        }
        return [];
    }

    /**
     * @return string|null
     */
    public function getRequestUuid()
    {
        if($this->bxHelperData->isEnabledOnLayer($this->layer) && $this->bxHelperData->isPluginEnabled() && !$this->bxHelperData->getFallback())
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
        if($this->bxHelperData->isEnabledOnLayer($this->layer) && $this->bxHelperData->isPluginEnabled() && !$this->bxHelperData->getFallback())
        {
            return $this->p13nHelper->getRequestGroupBy();
        }

        return null;
    }

}
