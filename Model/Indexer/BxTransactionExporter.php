<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Model\Indexer\BxIndexer;

/**
 * Class BxTransactionExporter
 * @package Boxalino\Intelligence\Model\Indexer
 */
class BxTransactionExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{

    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'boxalino_indexer_transactions';

    /**
     * Indexer type
     */
    const INDEXER_TYPE = "full";

    /**
     * @var \Boxalino\Intelligence\Model\Indexer\BxIndexer
     */
    protected $bxIndexer;

    /**
     * BxTransactionExporter constructor.
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
    public function executeFull()
    {
        $startExportDate = date("Y-m-d H:i:s");
        if(!$this->processManager->processCanRun())
        {
            return true;
        }

        try{
            $status = $this->processManager->run();
            if($status) {
                $this->processManager->updateProcessRunDate($startExportDate);
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

}