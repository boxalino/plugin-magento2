<?php
namespace Boxalino\Intelligence\Block\Widget;

/**
 * Class ProductFinder
 * @package Boxalino\Intelligence\Block
 */
class ProductFinder extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface {

    protected function _toHtml()
    {
        return $this->_layout->createBlock('Boxalino\Intelligence\Block\ProductFinder')->
        setTemplate($this->getData('bx_template'))->toHtml();
    }
}

