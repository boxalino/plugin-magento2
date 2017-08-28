<?php
namespace Boxalino\Intelligence\Model\Indexer;

/**
 * Class BxDeltaExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxDeltaExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{
    
    /**
     * @var BxIndexer
     */
    protected $bxIndexer;
    
    /**
     * BxDeltaExporter constructor.
     * @param BxIndexer $bxIndexer
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
     * @throws \Exception
     */
    public function execute($ids){
        $this->bxIndexer->setDeltaIds($ids)->setIndexerType('delta')->exportStores(true,false,false);
    }

    /**
     * Not used for delta export
     */
    public function executeFull(){
    }
}