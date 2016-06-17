<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Magento\Indexer\Model\Indexer;

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
    public function __construct(
       BxIndexer $bxIndexer
    )
    {
        $this->bxIndexer = $bxIndexer;
    }

    /**
     * @throws \Exception
     */
    public function executeFull(){
        $this->bxIndexer->setIndexerType('delta')->exportStores();
    }

    /**
     * @param array $ids
     */
    public function executeList(array $ids){
    }

    /**
     * @param int $id
     */
    public function executeRow($id){
    }

    /**
     * @param \int[] $ids
     */
    public function execute($ids){
    }
}