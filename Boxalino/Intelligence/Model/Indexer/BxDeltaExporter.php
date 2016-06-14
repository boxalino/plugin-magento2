<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Magento\Indexer\Model\Indexer;

class BxDeltaExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    
    protected $bxIndexer;
    public function __construct(
       BxIndexer $bxIndexer
    )
    {
        $this->bxIndexer = $bxIndexer;
    }

    public function executeFull(){
        $this->bxIndexer->setIndexerType('delta')->exportStores();
    }
    public function executeList(array $ids){
    }
    public function executeRow($id){
    }
    public function execute($ids){
    }
}