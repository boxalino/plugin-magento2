<?php
namespace Boxalino\Intelligence\Model\Indexer;

/**
 * Class BxExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

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
		$this->bxIndexer->setIndexerType('full')->exportStores();
	}

}