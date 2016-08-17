<?php
namespace Boxalino\Intelligence\Plugin\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class IndexPlugin
 * @package Boxalino\Intelligence\Plugin\Controller
 */
class IndexPlugin{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;


    /**
     * IndexPlugin constructor.
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

        if($this->bxHelperData->isSearchEnabled()){

            $objectManager = $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $configuration = array('Magento\CatalogSearch\Block\SearchResult\ListProduct' =>
                array('type'=>'Boxalino\Intelligence\Block\Product\BxListProducts')
            );
            $objectManager->configure($configuration);

            $configuration = array('searchFilterList' =>
                array('type'=>'Boxalino\Intelligence\Model\FilterList')
            );
            $objectManager->configure($configuration);

            $configuration = array('Magento\Catalog\Model\Layer\State' =>
                array('type'=>'Boxalino\Intelligence\Block\State')
            );
            $objectManager->configure($configuration);
        }
    }
}
