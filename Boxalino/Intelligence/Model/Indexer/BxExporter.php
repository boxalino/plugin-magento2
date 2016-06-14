<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Model\Indexer\BxIndexer;
class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

	protected $bxIndexer;
    public function __construct(
		BxIndexer $bxIndexer
    )
    {
		$this->bxIndexer = $bxIndexer;
    }

    public function executeRow($id){
		
    }

    public function executeList(array $ids){
    }

    public function execute($ids){
    }
	
    public function executeFull(){
		$this->bxIndexer->setIndexerType('full')->exportStores();
	}
}