<?php

namespace Boxalino\Frontend\Block;

class Facets extends \Magento\Framework\View\Element\Template
{	
	private $objectManager;
	private $p13nHelper;
	private $layer;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        array $data = []
    )
    {
        parent::__construct($context, $data);
		$this->p13nHelper = $p13nHelper;
		$this->layer = $layerResolver->get();
		$this->objectManager = $objectManager;
    }

    public function getTopFilters()
    {
        $facets = $this->p13nHelper->getFacets();
		$fieldName = $this->p13nHelper->getTopFacetFieldName();
        $attribute = $this->objectManager->create("Magento\Catalog\Model\ResourceModel\Eav\Attribute");
		$filter = $this->objectManager->create(
			"Boxalino\Frontend\Model\Attribute",
			['data' => ['attribute_model' => $attribute], 'layer' => $this->layer]
		);
		$filter->setFacets($facets);
		$filter->setFieldName($fieldName);
		return $filter->getItems();
    }
}
