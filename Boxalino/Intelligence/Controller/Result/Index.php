<?php namespace Boxalino\Intelligence\Controller\Result;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;

/**
 * Class Index
 * @package Boxalino\Intelligence\Controller
 */
class Index extends \Magento\CatalogSearch\Controller\Result\Index
{
    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13Helper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;
    
    /**
     * @var QueryFactory
     */
    private $_queryFactory;

    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Index constructor.
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param Context $context
     * @param Session $catalogSession
     * @param StoreManagerInterface $storeManager
     * @param QueryFactory $queryFactory
     * @param Resolver $layerResolver
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        Context $context,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context,$catalogSession,$storeManager,$queryFactory,$layerResolver);
        $this->_logger = $logger;
        $this->bxHelperData = $bxHelperData;
        $this->p13Helper = $p13nHelper;
        $this->_queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
    }
    
    /**
     * Display search result
     *
     * @return void
     */
    public function execute()
    {
        if($this->bxHelperData->isSearchEnabled()){

            $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
            /* @var $query \Magento\Search\Model\Query */
            $query = $this->_queryFactory->get();

            try{
                if($this->p13Helper->areThereSubPhrases()){
                    $queries = $this->p13Helper->getSubPhrasesQueries();
                    if(count($queries) == 1){
                        $this->_redirect('*/*/*', array('_current'=> true, '_query' => array('q' => $queries[0])));
                    }
                }
                if($this->p13Helper->areResultsCorrected()){
                    $correctedQuery = $this->p13Helper->getCorrectedQuery();
                    $query->setQueryText($correctedQuery);
                }
            }catch(\Exception $e){
                $this->bxHelperData->setFallback(true);
                $this->_logger->critical($e);
            }
            $query->setStoreId($this->_storeManager->getStore()->getId());

            if ($query->getQueryText() != '') {
                if ($this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->isMinQueryLength()) {
                    $query->setId(0)->setIsActive(1)->setIsProcessed(1);
                } else {
                    $query->saveIncrementalPopularity();

                    if ($query->getRedirect()) {
                        $this->getResponse()->setRedirect($query->getRedirect());
                        return;
                    }
                }

                $this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->checkNotes();
                $this->_view->loadLayout();
                $this->_view->renderLayout();
            } else {
                $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
            }
        }else{
            parent::execute();
        } 
    }
}