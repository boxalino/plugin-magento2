<?php
namespace Boxalino\Intelligence\Model\Indexer;

/**
 * Class BxExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'boxalino_indexer';

    /**
     * Indexer type
     */
    const INDEXER_TYPE = "full";

    /**
     * @var \Boxalino\Intelligence\Model\Indexer\BxIndexer
     */
    protected $bxIndexer;

    /**
     * BxExporter constructor.
     * @param \Boxalino\Intelligence\Model\Indexer\BxIndexer $bxIndexer
     */
    public function __construct(BxIndexer $bxIndexer)
    {
        $this->bxIndexer = $bxIndexer;
    }

    /**
     * @param int $id
     */
    public function executeRow($id){}

    /**
     * @param array $ids
     */
    public function executeList(array $ids){}

    /**
     * @param \int[] $ids
     */
    public function execute($ids){}

    /**
     * @throws \Exception
     */
    public function executeFull(){
        try{
            $startExportDate = date("Y-m-d H:i:s");
            $status = $this->bxIndexer->setIndexerType(self::INDEXER_TYPE)
                ->setIndexerId(self::INDEXER_ID)
                ->exportStores();

            if($status)
            {
                $this->bxIndexer->updateIndexerLatestDate(self::INDEXER_ID, $startExportDate);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

}