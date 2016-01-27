<?php
namespace Boxalino\Exporter\Block;
use Magento\Framework\View\Element\Template;

class Main extends Template{

    protected function _prepareLayout()
    {
        $this->setText(__CLASS__);
    }
}