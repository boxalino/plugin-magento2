<?php
namespace Boxalino\Intelligence\Model\Exporter;

use Boxalino\Intelligence\Model\ResourceModel\ProcessManager as ProcessManagerResource;
use Boxalino\Intelligence\Helper\BxFiles;
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
        ProcessManagerResource $processResource
    ) {
        $this->processResource = $processResource;
        $this->indexerModel = $indexer;
        $this->logger = $logger;
        $this->config = $bxIndexConfig;
        $this->exporterService = $service;

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
        
        $errorMessages = array();
        $successMessages = array();

        $this->logger->info("BxIndexLog: starting Boxalino {$this->getType()} export process. Latest update at {$this->getLatestRun()}");
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
                }
            } catch (\Exception $exception) {
                $errorMessages[] = $exception->getMessage();
                continue;
            }
        }
        
        if(empty($errorMessages))
        {
            return true;
        }
        
        throw new \Exception(__("Boxalino Export failed with messages: " . implode(",", $errorMessages)));
    }


    public function processCanRun()
    {
        if(($this->getType() == BxDeltaExporter::INDEXER_TYPE) &&  $this->indexerModel->load(BxExporter::INDEXER_ID)->isWorking())
        {
            $this->logger->info("bxLog: Delta exporter will not run. Full exporter process must finish first.");
            return false;
        }
        if(($this->getType() == BxExporter::INDEXER_TYPE) &&  $this->indexerModel->load(BxDeltaExporter::INDEXER_ID)->isWorking())
        {
            $this->logger->info("bxLog: Full exporter will not run. Delta exporter process must finish first.");
            return false;
        }
        
        return true;
    }

    public function exportAllowedByAccount($account)
    {
        if($this->exportDeniedOnAccount($account))
        {
            $this->logger->info("bxLog: The {$this->getType()} export is denied permission to run. Check your exporter configurations.");
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

    abstract function getTimeout($account);
    abstract function getLatestRun();
    abstract function getIds();
    abstract function exportDeniedOnAccount($account);
    abstract function getType() : string;
    abstract function getIndexerId() : string;
    abstract function getExportFull();

}