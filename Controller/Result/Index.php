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
            try{
                $start = microtime(true);
                $this->p13Helper->addNotification('debug', "request start at " . $start);
                $redirect_link = $this->p13Helper->getResponse()->getRedirectLink();
                $this->p13Helper->addNotification('debug',
                    "request end, time: " . (microtime(true) - $start) * 1000 . "ms" .
                    ", memory: " . memory_get_usage(true));

                if($redirect_link != "") {
                        $this->getResponse()->setRedirect($this->p13Helper->getResponse()->getRedirectLink());
                }

                $query = $this->_queryFactory->get();
                if($this->p13Helper->areThereSubPhrases()){
                    $queries = $this->p13Helper->getSubPhrasesQueries();
                    if(count($queries) == 1){
                        $this->_redirect('*/*/*', array('_current'=> true, '_query' => array('q' => $queries[0])));
                    }
                }
                if($this->p13Helper->areResultsCorrected()) {
                    $correctedQuery = $this->p13Helper->getCorrectedQuery();
                    $query->setQueryText($correctedQuery);
                }
                parent::execute();
            }catch(\Exception $e){
                $this->bxHelperData->setFallback(true);
                $this->_logger->critical($e);
            }
        } else {
            parent::execute();
        }
    }
}