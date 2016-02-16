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
    protected $bxSession;
    protected $storeManager;
    protected $order;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Boxalino\Frontend\Helper\Data $bxHelperData,
        \Boxalino\Frontend\Model\Session $bxSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order
    )
    {
        $this->order = $order;
        $this->storeManager = $storeManager;
        $this->bxSession = $bxSession;
        $this->bxHelperData = $bxHelperData;
        $this->messageManager = $messageManager;
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
            case "catalog_controller_product_view": //onProductPageView DONE
                $this->onProductPageView($event);
                break;
            case "catalog_controller_category_init_after": //onCategoryPageView DONE
                $this->onCategoryPageView($event);
                break;
            case "customer_login": //onLogin DONE
                $this->onLogin($event);
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

            $script = "_bxq.push(['trackAddToBasket', '" . $product . "', " . $count . ", " . $price . ", '" . $currency . "']);" . PHP_EOL;

            $this->bxSession->addScript($script);
        } catch (\Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
        }
    }

    public function onOrderSuccessPageView($event)
    {
        try {
            $orders = $this->order->getItemsCollection()
                ->setOrder('entity_id', 'DESC')
                ->setPageSize(1)
                ->setCurPage(1);
            $order = $orders->getFirstItem();
            $orderData = $order->getData();
            $transactionId = $orderData['entity_id'];
            $products = array();
            $fullPrice = 0;
            foreach ($order->getItems() as $item) {
                if ($item->getPrice() > 0) {
                    $products[] = array(
                        'product' => $item->getProduct()->getId(),
                        'quantity' => $item->getData('qty_ordered'),
                        'price' => $item->getPrice()
                    );
                    $fullPrice += $item->getPrice() * $item->getData('qty_ordered');
                }
            }
            $script = $this->bxHelperData->reportPurchase($products, $transactionId, $fullPrice, $this->storeManager->getStore()->getCurrentCurrencyCode());

            $this->bxSession->addScript($script);
        } catch (\Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
        }
    }

    public function onProductPageView($event)
    {
        try {
            $script = $this->bxHelperData->reportProductView($event->getProduct()->getId());
            $this->bxSession->addScript($script);
        } catch (\Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
        }
    }

    public function onCategoryPageView($event)
    {

        try {
            $script = $this->bxHelperData->reportCategoryView($event->getCategory()->getId());
            $this->bxSession->addScript($script);
        } catch (\Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
        }
    }

    public function onLogin($event)
    {
        try {
            $script = $this->bxHelperData->reportLogin($event->getCustomer()->getId());
            $this->bxSession->addScript($script);
        } catch (\Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
        }
    }
}
