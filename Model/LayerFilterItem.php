<?php

namespace Boxalino\Intelligence\Model;
/**
 * Class LayerFilterItem
 * @package Boxalino\Intelligence\Model
 */
class LayerFilterItem extends \Magento\Catalog\Model\Layer\Filter\Item {
    
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
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_layer;


    /**
     * LayerFilterItem constructor.
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param array $data
     */
	public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        array $data = []
    )
    {
        $this->_layer = $layerResolver->get();
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


	public function getRemoveUrl()
    {
        if($this->bxDataHelper->isEnabledOnLayer($this->_layer)) {
            $removeParams = $this->bxDataHelper->getRemoveParams();
            $addParams = $this->bxDataHelper->getSystemParams();
            $requestVar = $this->getFilter()->getRequestVar();
            $query = array($requestVar => $this->getValue());
            foreach ($removeParams as $remove) {
                $query[$remove] = null;
            }
            foreach ($addParams as $param => $add) {
                if($requestVar != $param){
                    $query = array_merge($query, [$param => implode($this->bxDataHelper->getSeparator(), $add)]);
                }
            }
            $params['_current']     = true;
            $params['_use_rewrite'] = true;
            $params['_query']       = $query;
            $params['_escape']      = true;
            return $this->_url->getUrl('*/*/*', $params);
        }
        return parent::getRemoveUrl();
    }

    public function getUrl()
    {
        if($this->bxDataHelper->isEnabledOnLayer($this->_layer)) {

            $removeParams = $this->bxDataHelper->getRemoveParams();
            $addParams = $this->bxDataHelper->getSystemParams();
            $requestVar = $this->getFilter()->getRequestVar();
            $query = array(
                $requestVar=>$this->getValue(),
                'p' => null // exclude current page from urls
            );
            foreach ($addParams as $param => $values) {
                if($requestVar != $param) {
                    $add = [$param => implode($this->bxDataHelper->getSeparator(), $values)];
                    $query = array_merge($query, $add);
                }
            }
            foreach ($removeParams as $remove) {
                $query[$remove] = null;
            }
            return$this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
        }
        return parent::getUrl();
    }
}
