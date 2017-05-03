<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class Facets
 * @package Boxalino\Intelligence\Block
 */
class Facets extends \Magento\Framework\View\Element\Template{

	/**
	 * @var \Magento\Framework\ObjectManagerInterface
	 */
	private $objectManager;

	/**
	 * @var \Boxalino\Intelligence\Helper\P13n\Adapter
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
	 * Facets constructor.
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
	 * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
	 * @param \Magento\Framework\ObjectManagerInterface $objectManager
	 * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
	 * @param array $data
	 */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
		\Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        array $data = []
    )
    {
        parent::__construct($context, $data);
		$this->_logger = $context->getLogger();
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
			if($this->bxHelperData->isEnabledOnLayer($this->layer)){
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
		return array();
    }
}
