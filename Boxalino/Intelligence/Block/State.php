<?php

namespace Boxalino\Intelligence\Block;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

/**
 * Class State
 * @package Boxalino\Intelligence\Block
 */
class State extends \Magento\Framework\DataObject{
    
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
	private $objectManager;

    /**
     * State constructor.
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
	public function __construct(
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
		array $data = []
    )
    {
        $this->_data = $data;
        $this->p13nHelper = $p13nHelper;
        $this->objectManager = $objectManager;
        parent::__construct($data);
    }

	/**
     * Add filter item to layer state
     *
     * @param   Item $filter
     * @return  $this
     */
    public function addFilter($filter){
        
        $filters = $this->getFilters();
        $filters[] = $filter;
        $this->setFilters($filters);
        return $this;
    }

    /**
     * Set layer state filter items
     *
     * @param  Item[] $filters
     * @return $this
     * @throws LocalizedException
     */
    public function setFilters($filters){
        
        if (!is_array($filters)) {
            throw new LocalizedException(__('The filters must be an array.'));
        }
        $this->setData('filters', $filters);
        return $this;
    }

    /**
     * Get applied to layer filter items
     *
     * @return Item[]
     */
    public function getFilters(){
        
		$filters = array();
        $facets = $this->p13nHelper->getFacets();
        foreach($this->p13nHelper->getAllFacetFieldNames() as $fieldName) {
            
			if($facets->isSelected($fieldName)) {
				$filter = $this->objectManager->create(
					"Boxalino\Intelligence\Model\LayerFilterItem"
				);

				$filter->setFacets($facets);
				$filter->setFieldName($fieldName);
                $filter->setClearLinkUrl("abc");
				$filters[] = $filter;
			}
        }
		return $filters;
    }
}
