<?php

namespace Boxalino\Intelligence\Model;
/**
 * Class LayerFilterItem
 * @package Boxalino\Intelligence\Model
 */
class LayerFilterItem extends \Magento\Catalog\Model\Layer\Filter\Item {
    
    /**
     * @var
     */
    private $filter;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
	protected $objectManager;
    
    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
	protected $bxDataHelper;

    /**
     * @var null
     */
	private $bxFacets = null;

    /**
     * @var array
     */
    private $fieldName = array();

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $_request;
    
    /**
     * LayerFilterItem constructor.
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
	public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Request\Http $request,
        array $data = []
    )
    {
        $this->_request = $request;
		$this->objectManager = $objectManager;
        $this->bxDataHelper = $bxDataHelper;
        parent::__construct($url, $htmlPagerBlock, $data);
    }

    /**
     * @param $bxFacets
     */
    public function setFacets($bxFacets) {
        
        $this->bxFacets = $bxFacets;
    }

    /**
     * @param $fieldName
     */
    public function setFieldName($fieldName) {
        
        $this->fieldName = $fieldName;
    }

    /**
     * @return mixed
     */
    public function getName(){
        
        return $this->bxFacets->getFacetLabel($this->fieldName);
    }

    /**
     * Get filter instance
     *
     * @return \Magento\Catalog\Model\Layer\Filter\AbstractFilter
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFilter(){
        
		if($this->filter == null) {
			$this->filter = $this->objectManager->create(
                "Boxalino\Intelligence\Model\LayerFilterFilter"
            );
            $requestParams = $this->_request->getParams();
            $parameterVar = $this->bxFacets->getFacetParameterName($this->fieldName);
            foreach ($requestParams as $key => $values) {
                if($this->fieldName == 'products_' . $key){
                    $parameterVar = $key;
                    break;
                }
            }
			$this->filter->setRequestVar($parameterVar);
			$this->filter->setCleanValue(null);
            $requestParams = $this->_request->getParams();
            if($this->bxDataHelper->isHierarchical($this->fieldName) && isset($requestParams['bx_category_id'])){
                $this->filter->setResetValue($this->bxFacets->getParentId($this->fieldName,$requestParams[($this->bxFacets->getFacetParameterName($this->fieldName))]));
            }
		}
		return $this->filter;
    }

    /**
     * @return mixed
     */
	public function getLabel() {
        
		return $this->bxFacets->getSelectedValueLabel($this->fieldName);
	}
}
