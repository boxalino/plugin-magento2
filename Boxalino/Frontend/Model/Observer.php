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
    protected $helperData;
    protected $bxSession;
    protected $storeManager;
    protected $order;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Boxalino\Frontend\Helper\Data $helperData,
        \Boxalino\Frontend\Model\Session $bxSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Sales\Model\Order $order
    )
    {
        $this->order = $order;
        $this->storeManager = $storeManager;
        $this->bxSession = $bxSession;
        $this->helperData = $helperData;
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
                $block = "order success";
                break;
            case "catalog_controller_product_view": //onProductPageView
                $block = "product view";
                break;
            case "catalog_controller_category_init_after": //onCategoryPageView
                $block = "category view";
                break;
            case "customer_login": //onLogin
                $block = "customer login";
                break;
            default:
                $block = "nichts";
                break;
        }

        $this->messageManager->addNotice(__('%1',$block));

    }

    public function onProductAddedToCart($event)
    {

        try {
            $session = $this->bxSession;
            $script = $this->helperData->reportAddToBasket(
                $event->getProduct()->getId(),
                $event->getQuoteItem()->getQty(),
                $event->getProduct()->getSpecialPrice() > 0 ? $event->getProduct()->getSpecialPrice() : $event->getProduct()->getPrice(),
                $this->storeManager->getStore()->getCurrentCurrencyCode()
            );
            $session->addScript($script);
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
            $script = Mage::helper('Boxalino_Frontend')->reportPurchase($products, $transactionId, $fullPrice, Mage::app()->getStore()->getCurrentCurrencyCode());

            $session = Mage::getSingleton('Boxalino_Frontend_Model_Session');
            $session->addScript($script);
        } catch (Exception $e) {
            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
                echo($e);
                exit;
            }
        }
    }

//    public function onProductPageView(Varien_Event_Observer $event)
//    {
//        try {
//            $productId = $event['product']->getId();
//            $script = Mage::helper('Boxalino_Frontend')->reportProductView($productId);
//
//            $session = Mage::getSingleton('Boxalino_Frontend_Model_Session');
//            $session->addScript($script);
//        } catch (Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
//        }
//    }
//
//    public function onCategoryPageView(Varien_Event_Observer $event)
//    {
//
//        try {
//            $categoryId = $event['category']['entity_id'];
//            $script = Mage::helper('Boxalino_Frontend')->reportCategoryView($categoryId);
//
//            $session = Mage::getSingleton('Boxalino_Frontend_Model_Session');
//            $session->addScript($script);
//        } catch (Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
//        }
//    }
//
//    public function onLogin(Varien_Event_Observer $event)
//    {
//        try {
//            $userId = $event['customer']['entity_id'];
//            $script = Mage::helper('Boxalino_Frontend')->reportLogin($userId);
//
//            $session = Mage::getSingleton('Boxalino_Frontend_Model_Session');
//            $session->addScript($script);
//        } catch (Exception $e) {
//            if (Mage::helper('Boxalino_Frontend')->isDebugEnabled()) {
//                echo($e);
//                exit;
//            }
//        }
//    }
}
