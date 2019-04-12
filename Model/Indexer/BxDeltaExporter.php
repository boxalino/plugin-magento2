<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Boxalino\Intelligence\Model\Exporter\Process\Delta as ProcessManager;

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
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * BxDeltaExporter constructor.
     */
    public function __construct(ProcessManager $processManager)
    {
        $this->processManager = $processManager;
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
    public function execute($ids){}

    /**
     * Run on execute full command
     * Run via the command line
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