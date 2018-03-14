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
     * @var \Boxalino\Intelligence\Model\Facet
     */
    protected $bxFacetModel;

    /**
     * State constructor.
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Block\Category\View $categoryViewBlock
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Boxalino\Intelligence\Model\Facet $facet
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
        \Boxalino\Intelligence\Model\Facet $facet,
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
        $this->bxFacetModel = $facet;
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

                $facets = $this->p13nHelper->getFacets();
                if ($facets) {
                    $filters = array();
                    foreach ($this->bxFacetModel->getFacets() as $filter) {
                        $fieldName = $filter->getFieldName();
                        if($facets->isSelected($fieldName)){
                            $items = $filter->getItems();
                            $selectedValues = $facets->getSelectedValues($fieldName);
                            if(!empty($selectedValues)) {
                                foreach ($selectedValues as $i => $v){
                                    $value = $facets->getSelectedValueLabel($fieldName, $i);
                                    if($fieldName == 'discountedPrice' && substr($value, -3) == '- 0') {
                                        $values = explode(' - ', $value);
                                        $values[1] = '*';
                                        $value = implode(' - ', $values);
                                    }
                                    if(isset($items[$value])){
                                        $item =  $items[$value];
                                        $filters[] = $item;
                                    }
                                }
                            } else {
                                $selectedValue = $facets->getSelectedValueLabel($fieldName);
                                if($selectedValue != '' && isset($items[$selectedValue])) {
                                    $item = $items[$selectedValue];
                                    $filters[] = $item;
                                }
                            }
                        }
                    }
                    return $filters;
                } else {
                    $this->p13nHelper->notifyWarning(["message"=>"BxFacets is not defined in " . get_class($this),
                        "stacktrace"=>$this->bxHelperData->notificationTrace()]);
                }
            }
            return parent::getFilters();
        }catch(\Exception $e){
            $this->bxHelperData->setFallback(true);
            $this->_logger->critical($e);
            return parent::getFilters();
        }
    }
}
