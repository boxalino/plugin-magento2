<?php

namespace Boxalino\Frontend\Model;

class LayerFilterItem extends \Magento\Catalog\Model\Layer\Filter\Item {

    private $filter;
	protected $objectManager;
	
	private $bxFacets = null;
    private $fieldName = array();
	
	public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
		$this->objectManager = $objectManager;
        parent::__construct($url, $htmlPagerBlock, $data);
    }

    public function setFacets($bxFacets) {
        $this->bxFacets = $bxFacets;
    }

    public function setFieldName($fieldName) {
        $this->fieldName = $fieldName;
    }

    public function getName()
    {
        return $this->bxFacets->getFacetLabel($this->fieldName);
    }

    /**
     * Get filter instance
     *
     * @return \Magento\Catalog\Model\Layer\Filter\AbstractFilter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFilter()
    {
		if($this->filter == null) {
			$this->filter = $this->objectManager->create(
                "Boxalino\Frontend\Model\LayerFilterFilter"
            );
			$this->filter->setRequestVar($this->bxFacets->getFacetParameterName($this->fieldName));
			$this->filter->setCleanValue(null);
			$this->filter->setClearLinkText(null);
			$this->filter->setResetValue(null);
		}
		return $this->filter;
    }
	
	public function getLabel() {
		return $this->bxFacets->getSelectedValueLabel($this->fieldName);
	}
}
