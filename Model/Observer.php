<?php
namespace Boxalino\Intelligence\Model;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject as Object;

/**
 * Class Observer
 * @package Boxalino\Intelligence\Model
 */
class Observer implements ObserverInterface{
    
    /**
     * @var
     */
    protected $messageManager;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $order;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     * Observer constructor.
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Model\Order $order
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_logger = $logger;
        $this->order = $order;
        $this->storeManager = $storeManager;
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * @param $script
     */
    protected function addScript($script){
        
        $this->bxHelperData->addScript($script);
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        
            $event = $observer->getEvent();
            switch($event->getName()){
                case "checkout_cart_add_product_complete": //onProductAddedToCart
                    break;
                case "checkout_onepage_controller_success_action": //onOrderSuccessPageView
                    $this->onOrderSuccessPageView($event);
                    break;
                case "catalog_controller_product_view": //onProductPageView
                    $this->onProductPageView($event);
                    break;
                case "catalog_controller_category_init_after": //onCategoryPageView
                    $this->onCategoryPageView($event);
                    break;
                default:
                    break;
            }
    }

    /**
     * @param $event
     * @throws \Exception
     */
    private function onOrderSuccessPageView($event){
        
        try {
            $orders = $this->order->getCollection()
                ->setOrder('entity_id', 'DESC')
                ->setPageSize(1)
                ->setCurPage(1);
            $order = $orders->getFirstItem();
            $orderData = $order->getData();
            $transactionId = $orderData['entity_id'];
            $products = array();
            foreach ($order->getAllItems() as $item) {
                if ($item->getPrice() > 0) {
                    $products[] = array(
                        'product' => $item->getProduct()->getId(),
                        'quantity' => $item->getData('qty_ordered'),
                        'price' => $item->getPrice()
                    );
                }
            }
            $fullPrice = $orderData['grand_total'];
            $currency = $orderData['base_currency_code'];
            $script = $this->bxHelperData->reportPurchase($products, $transactionId, $fullPrice, $currency);

            $this->addScript($script);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $event
     * @throws \Exception
     */
    protected function onProductPageView($event){
        
        try {
            $script = $this->bxHelperData->reportProductView($event->getProduct()->getId());
            $this->addScript($script);

        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $event
     * @throws \Exception
     */
    protected function onCategoryPageView($event){
        
        try {
            $script = $this->bxHelperData->reportCategoryView($event->getCategory()->getId());
            $this->addScript($script);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}
