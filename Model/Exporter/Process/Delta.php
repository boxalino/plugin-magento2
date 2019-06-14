<?php
namespace Boxalino\Intelligence\Model\Exporter\Process;

use Boxalino\Intelligence\Model\Exporter\ProcessManager;

class Delta extends ProcessManager
{

    /**
     * Indexer ID in configuration
     */
    const INDEXER_ID = 'boxalino_indexer_delta';

    const INDEXER_TYPE = 'delta';

    /**
     * Default server timeout
     */
    const SERVER_TIMEOUT_DEFAULT = 60;

    /**
     * @var array
     */
    protected $ids = [];

    /**
     * stop execution if there are no deltas
     *
     * @return bool
     */
    public function run()
    {
        $ids = $this->getIds();
        if(empty($ids))
        {
            $latestRun = $this->getLatestRun();
            $this->logger->info("BxIndexLog: The delta export is empty at {$this->getUtcTime()} (UTC) / {$this->getCurrentStoreTime()} (store time). Latest update at {$latestRun} (UTC)  / {$this->getStoreTime($latestRun)} (store time). Closing request.");
            return true;
        }

        $this->logger->info("BxIndexLog: The delta export has " . count($ids) . " products to update in stack.");
        return parent::run();
    }

    public function getType(): string
    {
        return self::INDEXER_TYPE;
    }

    public function getIndexerId(): string
    {
        return self::INDEXER_ID;
    }

    /**
     * Get timeout for exporter
     * @return bool|int
     */
    public function getTimeout($account)
    {
        return self::SERVER_TIMEOUT_DEFAULT;
    }

    /**
     * If the exporter scheduler is enabled, the delta export time has to be validated
     * 1. the delta can only be triggered between configured start-end hours
     * 2. 2 subsequent deltas can only be run with the time difference configured
     * 3. the delta after a full export can only be run after the configured time
     *
     * @param $startExportDate
     * @return bool
     */
    public function exportDeniedOnAccount($account)
    {
        if(!$this->config->isExportSchedulerEnabled($account))
        {
            return false;
        }

        $startHour = $this->config->getExportSchedulerDeltaStart($account);
        $endHour = $this->config->getExportSchedulerDeltaEnd($account);
        $runDateStoreHour = $this->getCurrentStoreTime('H');;
        if($runDateStoreHour === min(max($runDateStoreHour, $startHour), $endHour))
        {
            $latestDeltaRunDate = $this->getLatestUpdatedAt($this->getIndexerId());
            $deltaTimeRange = $this->config->getExportSchedulerDeltaMinInterval($account);
            if($latestDeltaRunDate == min($latestDeltaRunDate, date("Y-m-d H:i:s", strtotime("-$deltaTimeRange min"))))
            {
                return false;
            }

            return true;
        }

        return true;
    }

    /**
     * Check latest run on delta
     *
     * @return false|string|null
     */
    public function getLatestRun()
    {
        if(is_null($this->latestRun))
        {
            $this->latestRun = $this->getLatestUpdatedAt($this->getIndexerId());
        }

        if(empty($this->latestRun) || strtotime($this->latestRun) < 0)
        {
            $this->latestRun = date("Y-m-d H:i:s", strtotime("-1 hour"));
        }

        return $this->latestRun;
    }

    /**
     * @return array
     */
    public function getIds()
    {
        $lastUpdateDate = $this->getLatestRun();
        $directProductUpdates = $this->processResource->getProductIdsByUpdatedAt($lastUpdateDate);
        $categoryProductUpdates = $this->processResource->getAffectedEntityIds(self::INDEXER_ID);

        $ids = array_filter(array_unique(array_merge($directProductUpdates, explode(",", $categoryProductUpdates))));
        if(empty($ids))
        {
            return array();
        }
        
        return $ids;
    }

    /**
     * @return bool
     */
    public function getExportFull()
    {
        return false;
    }

    /**
     * resetting the affected products in case of a succesfull execution of delta export
     */
    public function updateAffectedProductIds()
    {
        $this->processResource->updateAffectedEntityIds($this->getIndexerId(), "");
    }

}