<?php
namespace Boxalino\Intelligence\Model;
/**
 * Class BxDataBuilder
 * @package Boxalino\Intelligence\Model
 */
class BxDataBuilder extends \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxDataHelper;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    private $_layer;
    
    /**
     * BxDataBuilder constructor.
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     */
    public function __construct(
        \Boxalino\Intelligence\Helper\Data $bxDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver
    ){
        $this->_layer = $layerResolver->get();
        $this->bxDataHelper = $bxDataHelper;
    }

    /**
     * @param string $label
     * @param $value
     * @param int $count
     * @param null $selected
     * @param null $type
     */
    public function addItemData($label, $value, $count, $selected = null, $type = null){
        
        if($this->bxDataHelper->isFilterLayoutEnabled($this->_layer instanceof \Magento\Catalog\Model\Layer\Category)){
            $this->_itemsData[] = [
                'label' => $label,
                'value' => $value,
                'count' => $count,
                'selected' => $selected,
                'type' => $type
            ];
        }else{
            $this->_itemsData[] = [
                'label' => $label,
                'value' => $value,
                'count' => $count
            ];
        }
    }
}