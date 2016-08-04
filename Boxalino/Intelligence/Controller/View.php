<?php
namespace Boxalino\Intelligence\Controller;
use Magento\Catalog\Controller\Category\View as Mage_View;

/**
 * Class View
 * @package Boxalino\Intelligence\Controller
 */
class View extends Mage_View{

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
        
        $configuration = array('categoryFilterList' =>
            array('type'=>'Boxalino\Intelligence\Model\FilterList')
        );
        $this->_objectManager->configure($configuration);
        return parent::execute();
    }
}