<?php
namespace Boxalino\Intelligence\Model\Config\Source;

/**
 * Class Modes
 * @package Boxalino\Intelligence\Model\Config\Source
 */
class TransactionMode implements \Magento\Framework\Option\ArrayInterface{


    public function toOptionArray()
    {
        return [['value' => 1, 'label' => 'Full'], ['value' => 0, 'label' => 'Incremental']];
    }
}
