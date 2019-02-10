<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Magento\Framework\Mview\View\ChangelogTableNotExistsException;

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
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var view
     */
    protected $view;

    /**
     * BxDeltaExporter constructor.
     * @param BxIndexer $bxIndexer
     */
    public function __construct(
        BxIndexer $bxIndexer,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory
    ){
        $this->bxIndexer = $bxIndexer;
        $this->indexerFactory = $indexerFactory;
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
        $lastUpdate = $this->getLatestUpdated();
        $this->bxIndexer->setDeltaIds($ids)->setLatestDeltaUpdate($lastUpdate)->setIndexerType(self::INDEXER_TYPE)->exportStores(true,false,false);
    }

    /**
     * Run on execute full command
     * Run via the command line
     */
    public function executeFull(){
        $lastUpdate = $this->getLatestUpdated();
        $entityIdsFromChangeLog = $this->getChangeLogIds();
        try{
            $status = $this->bxIndexer->setLatestDeltaUpdate($lastUpdate)
                ->setDeltaIds($entityIdsFromChangeLog)
                ->setIndexerType(self::INDEXER_TYPE)
                ->exportStores(true,false,false);

            if($status) {
                $this->view->clearChangelog();
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            exit;
        }
    }

    /**
     * Latest run of the delta indexer
     *
     * @return string
     */
    protected function getLatestUpdated()
    {
        $indexer = $this->indexerFactory->create();
        $indexer->load(self::INDEXER_ID);
        $this->view = $indexer->getView();

        return $indexer->getLatestUpdated();
    }

    /**
     * Change log product IDs in case the mview is used
     *
     * @return array
     */
    protected function getChangeLogIds()
    {
        try {
            $currentVersionId = $this->view->getChangelog()->getVersion();
        } catch (ChangelogTableNotExistsException $e) {
            return [];
        }

        $lastVersionId = (int) $this->view->getState()->getVersionId();
        $ids = $this->view->getChangelog()->getList($lastVersionId, $currentVersionId);

        return $ids;
    }
}