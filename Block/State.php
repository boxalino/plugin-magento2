<?php

namespace Boxalino\Intelligence\Block;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

/**
 * Class State
 * @package Boxalino\Intelligence\Block
 */
class State extends \Magento\Catalog\Model\Layer\State{

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
	private $objectManager;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    private $_layer;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Catalog\Block\Category\View
     */
    private $_categoryViewBlock;

    /**
     * State constructor.
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Block\Category\View $categoryViewBlock
     * @param array $data
     */
	public function __construct(
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Block\Category\View $categoryViewBlock,
        \Psr\Log\LoggerInterface $logger,
		array $data = []
    )
    {
        $this->_logger = $logger;
        $this->_categoryViewBlock = $categoryViewBlock;
        $this->_layer = $layerResolver->get();
        $this->bxHelperData = $bxHelperData;
        $this->_data = $data;
        $this->p13nHelper = $p13nHelper;
        $this->objectManager = $objectManager;
        parent::__construct($data);
    }

    /**
     * Get applied to layer filter items
     *
     * @return Item[]
     */
    public function getFilters()
    {
        try {
            if ($this->bxHelperData->isEnabledOnLayer($this->_layer)) {
                $category = $this->_categoryViewBlock->getCurrentCategory();
                if($category != null && $category->getDisplayMode() == \Magento\Catalog\Model\Category::DM_PAGE){
                    return parent::getFilters();
                }
                $filters = array();
                $facets = $this->p13nHelper->getFacets();
                if ($facets) {
                    foreach ($facets->getLeftFacets() as $fieldName) {

                        if ($facets->isSelected($fieldName)) {
                            $filter = $this->objectManager->create(
                                "Boxalino\Intelligence\Model\LayerFilterItem"
                            );

                            $filter->setFacets($facets);
                            $filter->setFieldName($fieldName);
                            $filters[] = $filter;
                            $filter = null;
                        }
                    }
                } else {
                    $this->p13nHelper->notifyWarning(["message"=>"BxFacets is not defined in " . get_class($this),
                        "stacktrace"=>$this->bxHelperData->notificationTrace()]);
                }
                return $filters;
            }
            return parent::getFilters();
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
            return parent::getFilters();
        }
    }
}
