<?php
namespace Boxalino\Intelligence\Plugin\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class AjaxController
 * @package Boxalino\Intelligence\Controller
 */
class IndexPlugin{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;


    /**
     * AjaxController constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param AutocompleteInterface $autocomplete
     * @param \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct
     */
    public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData
    
    )
    {
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|null
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
