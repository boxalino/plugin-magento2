<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Model\Indexer\BxIndexer;

/**
 * Class BxDeltaExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxDeltaExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'boxalino_indexer_delta';

    const INDEXER_TYPE = 'delta';

    /**
     * @var BxIndexer
     */
    protected $bxIndexer;

    /**
     * BxDeltaExporter constructor.
     * @param BxIndexer $bxIndexer
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
     * In case of a scheduled update, it will be run
     *
     * @param \int[] $ids
     * @throws \Exception
     */
    public function execute($ids){
        $startExportDate = date("Y-m-d H:i:s");
        try{
            $status = $this->bxIndexer->setDeltaIds($ids)
                ->setIndexerType(self::INDEXER_TYPE)
                ->setIndexerId(self::INDEXER_ID)
                ->exportStores(true,false,false);

            if($status) {
                $this->bxIndexer->updateIndexerLatestDate(self::INDEXER_ID, $startExportDate);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Run on execute full command
     * Run via the command line
     */
    public function executeFull(){
        $startExportDate = date("Y-m-d H:i:s");
        try{
            $status = $this->bxIndexer->setIndexerType(self::INDEXER_TYPE)
                ->setIndexerId(self::INDEXER_ID)
                ->exportStores(true,false,false);

            if($status) {
                $this->bxIndexer->updateIndexerLatestDate(self::INDEXER_ID, $startExportDate);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}