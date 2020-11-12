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
     * Run when the MVIEW is in use (Update by Schedule)
     *
     * @param int[] $ids
     * @return bool|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($ids)
    {
        if($this->processManager->processCanRun())
        {
            if(is_array($ids))
            {
                try{
                    $startExportDate = $this->processManager->getUtcTime();
                    $this->processManager->setIds($ids);
                    $status = $this->processManager->run();
                    if($status) {
                        $this->processManager->updateProcessRunDate($startExportDate);
                        $this->processManager->updateAffectedProductIds();
                    }
                } catch (\Exception $exception) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * Run on execute full command
     * Run via the command line
     */
    public function executeFull()
    {
        if($this->processManager->processCanRun())
        {
            try{
                $startExportDate = $this->processManager->getUtcTime();
                $status = $this->processManager->run();
                if($status) {
                    $this->processManager->updateProcessRunDate($startExportDate);
                    $this->processManager->updateAffectedProductIds();
                }
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
    }


}
