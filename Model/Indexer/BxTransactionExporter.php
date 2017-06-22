<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Model\Indexer\BxIndexer;

/**
 * Class BxExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxTransactionExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    /**
     * @var \Boxalino\Intelligence\Model\Indexer\BxIndexer
     */
    protected $bxIndexer;

    /**
     * BxExporter constructor.
     * @param \Boxalino\Intelligence\Model\Indexer\BxIndexer $bxIndexer
     */
    public function __construct(BxIndexer $bxIndexer){

        $this->bxIndexer = $bxIndexer;
    }

    /**
     * @param int $id
     */
    public function executeRow($id){
    }

    /**
     * @param array $ids
     */
    public function executeList(array $ids){
    }

    /**
     * @param \int[] $ids
     */
    public function execute($ids){
    }

    /**
     * @throws \Exception
     */
    public function executeFull(){

        $this->bxIndexer->setIndexerType('full')->exportStores(false,false,true);
    }
}