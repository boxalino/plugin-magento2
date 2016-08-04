<?php
namespace Boxalino\Intelligence\Controller;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;

/**
 * Class IndexController
 * @package Boxalino\Intelligence\Controller
 */
class IndexController extends \Magento\CatalogSearch\Controller\Result\Index{
    
    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * IndexController constructor.
     * @param Context $context
     * @param Session $catalogSession
     * @param StoreManagerInterface $storeManager
     * @param QueryFactory $queryFactory
     * @param Resolver $layerResolver
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     */
    public function __construct(
        Context $context,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Boxalino\Intelligence\Helper\Data $bxHelperData
    )
    {
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $catalogSession, $storeManager, $queryFactory, $layerResolver);
    }

    /**
     * Setting Abstraction-Implementation mappings for Boxalino classes
     * @inheritdoc
     */
    public function execute(){
        
		if($this->bxHelperData->isSearchEnabled()){

            $configuration = array('Magento\CatalogSearch\Block\SearchResult\ListProduct' =>
                array('type'=>'Boxalino\Intelligence\Block\Product\BxListProducts')
            );
            $this->_objectManager->configure($configuration);

            $configuration = array('searchFilterList' =>
                array('type'=>'Boxalino\Intelligence\Model\FilterList')
            );
            $this->_objectManager->configure($configuration);

            $configuration = array('Magento\Catalog\Model\Layer\State' =>
                array('type'=>'Boxalino\Intelligence\Block\State')
            );
            $this->_objectManager->configure($configuration);
        }
        return parent::execute();
    }
}