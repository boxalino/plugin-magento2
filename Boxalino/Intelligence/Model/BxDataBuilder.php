<?php
namespace Boxalino\Intelligence\Model;
class BxDataBuilder extends \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder{

    protected $bxDataHelper;
    public function __construct(
        \Boxalino\Intelligence\Helper\Data $bxDataHelper
    )
    {
        $this->bxDataHelper = $bxDataHelper;
    }

    public function addItemData($label, $value, $count, $selected = null, $type = null)
    {
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