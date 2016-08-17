<?php
namespace Boxalino\Intelligence\Plugin\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class ViewPlugin
 * @package Boxalino\Intelligence\Plugin\Controller
 */
class ViewPlugin{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;


    /**
     * ViewPlugin constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     */
    public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData

    )
    {
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * 
     */
    public function beforeExecute(){

        if($this->bxHelperData->isFilterLayoutEnabled()){
            $objectManager = $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $configuration = array('categoryFilterList' =>
                array('type'=>'Boxalino\Intelligence\Model\FilterList')
            );
            $objectManager->configure($configuration);
        }
    }
}
