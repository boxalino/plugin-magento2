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
     * BxDataBuilder constructor.
     * @param \Boxalino\Intelligence\Helper\Data $bxDataHelper
     */
    public function __construct(\Boxalino\Intelligence\Helper\Data $bxDataHelper){
        
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
        
        if($this->bxDataHelper->isFilterLayoutEnabled()){
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