<?php

namespace Boxalino\Frontend\Block;

use Magento\Catalog\Model\Layer\Filter\Item;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\DataObject;

class State extends \Magento\Framework\DataObject
{	
    private $p13nHelper;
	private $objectManager;
	public function __construct(
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
		array $data = []
		)
    {
        $this->_data = $data;
        $this->p13nHelper = $p13nHelper;
        $this->objectManager = $objectManager;
    }
	
	/**
     * Add filter item to layer state
     *
     * @param   Item $filter
     * @return  $this
     */
    public function addFilter($filter)
    {
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
    public function setFilters($filters)
    {
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
    public function getFilters()
    {
		$filters = array();
        $facets = $this->p13nHelper->getFacets();
        foreach($this->p13nHelper->getAllFacetFieldNames() as $fieldName) {
            
			if($facets->isSelected($fieldName)) {
				$filter = $this->objectManager->create(
					"Boxalino\Frontend\Model\LayerFilterItem"
				);

				$filter->setFacets($facets);
				$filter->setFieldName($fieldName);
				$filters[] = $filter;
			}
        }
		return $filters;
    }
}
