<?php
namespace Boxalino\Frontend\Model;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\DataObject as Object;

/**
 * Boxalino CemExport event observer
 *
 * @author nitro@boxalino.com
 */
class Observer implements ObserverInterface
{
    protected $messageManager;
    protected $bxHelperData;
    protected $storeManager;
    protected $order;
    protected $logger;
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Boxalino\Frontend\Helper\Data $bxHelperData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Logger\Monolog $logger,
        \Magento\Sales\Model\Order $order
    )
    {
        $this->order = $order;
        $this->storeManager = $storeManager;
        $this->bxHelperData = $bxHelperData;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function addScript($script)
    {
        $this->bxHelperData->addScript($script);
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
            $event = $observer->getEvent();
            switch($event->getName()){
                case "checkout_cart_add_product_complete": //onProductAddedToCart
                    $this->onProductAddedToCart($event);
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

    public function onProductAddedToCart($event)
    {
        try {
            $product = $event->getProduct()->getId();
            $count = $event->getProduct()->getQty();
            $price = $event->getProduct()->getSpecialPrice() > 0 ? $event->getProduct()->getSpecialPrice() : $event->getProduct()->getPrice();
            $currency = $this->storeManager->getStore()->getCurrentCurrencyCode();

            $script = $this->bxHelperData->reportAddToBasket($product,$count,$price,$currency);
            $this->addScript($script);
        } catch (\Exception $e) {
        }
    }

    public function onOrderSuccessPageView($event)
    {
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
        }
    }

    public function onProductPageView($event)
    {
        try {
            $script = $this->bxHelperData->reportProductView($event->getProduct()->getId());
            $this->addScript($script);

        } catch (\Exception $e) {
        }
    }

    public function onCategoryPageView($event)
    {

        try {
            $script = $this->bxHelperData->reportCategoryView($event->getCategory()->getId());
            $this->addScript($script);
        } catch (\Exception $e) {
        }
    }
}
