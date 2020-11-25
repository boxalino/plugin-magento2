<?php
namespace Boxalino\Intelligence\Model\Exporter;

use Boxalino\Intelligence\Model\ResourceModel\ProcessManager as ProcessManagerResource;
use Boxalino\Intelligence\Helper\BxIndexConfig;
use Boxalino\Intelligence\Model\Indexer\BxDeltaExporter;
use Boxalino\Intelligence\Model\Indexer\BxExporter;
use Boxalino\Intelligence\Model\Exporter\Service as ExporterService;
use \Psr\Log\LoggerInterface;

abstract class ProcessManager
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Boxalino\Intelligence\Helper\BxIndexConfig : containing the access to the configuration of each store to export
     */
    protected $config = null;

    /**
     * @var []
     */
    protected $deltaIds = [];

    /**
     * @var \Magento\Indexer\Model\Indexer
     */
    protected $indexerModel;

    /**
     * @var ProcessManagerResource
     */
    protected $processResource;

    /**
     * @var null
     */
    protected $latestRun = null;

    /**
     * @var \Boxalino\Intelligence\Model\Exporter\Service 
     */
    protected $exporterService;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var array | null
     */
    protected $ids = null;

    /**
     * ProcessManager constructor.
     * 
     * @param LoggerInterface $logger
     * @param \Magento\Indexer\Model\Indexer $indexer
     * @param BxIndexConfig $bxIndexConfig
     * @param ExporterResourceInterface $exporterResource
     */
    public function __construct(
        LoggerInterface $logger,
        ExporterService $service,
        \Magento\Indexer\Model\Indexer $indexer,
        BxIndexConfig $bxIndexConfig,
        ProcessManagerResource $processResource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->processResource = $processResource;
        $this->indexerModel = $indexer;
        $this->logger = $logger;
        $this->config = $bxIndexConfig;
        $this->exporterService = $service;
        $this->timezone = $timezone;

        $libPath = __DIR__ . '/../../Lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }


    public function run()
    {
        $configurations = $this->config->toString();
        if(empty($configurations))
        {
            $this->logger->info("BxIndexLog: no active configurations found on either of the stores. Process cancelled.");
            return false;
        }
        
        $errorMessages = [];
        $latestRun = $this->getLatestRun();
        $this->logger->info("BxIndexLog: starting Boxalino {$this->getType()} export process. Latest update at {$latestRun} (UTC)  / {$this->getStoreTime($latestRun)} (store time)");
        $exporterHasRun = false;
        foreach($this->getAccounts() as $account)
        {
            try{
                if($this->exportAllowedByAccount($account))
                {
                    $this->exporterService
                        ->setAccount($account)
                        ->setDeltaIds($this->getIds())
                        ->setIndexerType($this->getType())
                        ->setIndexerId($this->getIndexerId())
                        ->setExportFull($this->getExportFull())
                        ->setTimeoutForExporter($this->getTimeout($account))
                        ->export();

                    $exporterHasRun = true;
                }
            } catch (\Exception $exception) {
                $errorMessages[] = $exception->getMessage();
                continue;
            }
        }
        
        if(!$exporterHasRun)
        {
            return false;
        }
        
        if(empty($errorMessages) && $exporterHasRun)
        {
            return true;
        }
        
        throw new \Exception(__("BxIndexLog: Boxalino Export failed with messages: " . implode(",", $errorMessages)));
    }


    public function processCanRun()
    {
        if(($this->getType() == BxDeltaExporter::INDEXER_TYPE) &&  $this->indexerModel->load(BxExporter::INDEXER_ID)->isWorking())
        {
            $this->logger->info("BxIndexLog: Delta exporter will not run. Full exporter process must finish first.");
            return false;
        }
        
        return true;
    }

    public function exportAllowedByAccount($account)
    {
        if($this->exportDeniedOnAccount($account))
        {
            $this->logger->info("BxIndexLog: The {$this->getType()} export is denied permission to run. Check your exporter configurations.");
            return false;
        }

        return true;
    }
    

    public function getAccounts()
    {
        return $this->config->getAccounts();
    }
    
    /**
     * Get indexer latest updated at
     *
     * @param $id
     * @return string
     */
    public function getLatestUpdatedAt($id)
    {
        return $this->processResource->getLatestUpdatedAtByIndexerId($id);
    }

    /**
     * @param $indexerId
     * @param $date
     */
    public function updateProcessRunDate($date)
    {
        $this->processResource->updateIndexerUpdatedAt($this->getIndexerId(), $date);
    }

    public function getCurrentStoreTime($format = 'Y-m-d H:i:s')
    {
        return $this->timezone->date()->format($format);
    }

    public function getStoreTime($date)
    {
        return $this->timezone->formatDate($date, 1, true);
    }

    public function getUtcTime($time=null)
    {
        if(is_null($time)){
            return $this->timezone->convertConfigTimeToUtc($this->getCurrentStoreTime());
        }

        return $this->timezone->convertConfigTimeToUtc($time);
    }

    /**
     * @param array $ids
     * @return \Boxalino\Exporter\Model\ProcessManager
     */
    public function setIds(array $ids)
    {
        $this->ids = array_unique($ids) ?? [];
        $this->ids = $this->addParentChildMatchToIds($this->ids);
        
        return $this;
    }

    /**
     * @param array $ids
     * @return array
     */
    protected function addParentChildMatchToIds(array $ids) : array
    {
        if(empty($ids))
        {
            return $ids;
        }

        $updatedWithParentChildMatches = $this->processResource->getChildParentIds($ids);
        return array_unique(array_merge(array_column($updatedWithParentChildMatches, "entity_id"), $ids));
    }

    abstract function getTimeout($account);
    abstract function getLatestRun();
    abstract function getIds();
    abstract function exportDeniedOnAccount($account);
    abstract function getType() : string;
    abstract function getIndexerId() : string;
    abstract function getExportFull();

}