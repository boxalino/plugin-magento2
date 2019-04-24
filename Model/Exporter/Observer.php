<?php
namespace Boxalino\Intelligence\Model\Exporter;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as ObserverObject;
use Boxalino\Intelligence\Model\Indexer\BxDeltaExporter;


/**
 * Class Observer
 * Stores products updated via category events
 *
 * @package Boxalino\Intelligence\Model\Exporter
 */
class Observer implements ObserverInterface
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Model\ResourceModel\ProcessManager
     */
    protected $processManager;

    /**
     * Observer constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Boxalino\Intelligence\Model\ResourceModel\ProcessManager $processManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->processManager = $processManager;
        $this->_logger = $logger;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(ObserverObject $observer)
    {
        try {
            $categoryAffectedProducts = $observer->getEvent()->getDataObject()->getAffectedProductIds();
            if(empty($categoryAffectedProducts))
            {
                return;
            }

            $finalAffectedProducts = $this->getAffectedProductsList($categoryAffectedProducts);
            $this->processManager->updateAffectedEntityIds(BxDeltaExporter::INDEXER_ID, implode(",", $finalAffectedProducts));
        } catch (\Exception $e) {
            $this->_logger->warning($e);
        }
    }

    /**
     * joining existed affected products with the new ones added to the list
     *
     * @param array $newIds
     * @return array
     */
    protected function getAffectedProductsList($newIds=array())
    {
        $oldIds = $this->processManager->getAffectedEntityIds(BxDeltaExporter::INDEXER_ID);
        return array_filter(array_unique(array_merge(explode(",", $oldIds), $newIds)));
    }

}