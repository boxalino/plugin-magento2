<?php
namespace Boxalino\Intelligence\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class AjaxController
 * @package Boxalino\Intelligence\Controller
 */
class AjaxController extends \Magento\Search\Controller\Ajax\Suggest
{
    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * AjaxController constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param AutocompleteInterface $autocomplete
     */
	public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        AutocompleteInterface $autocomplete,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->bxHelperData = $bxHelperData;
        $this->storeManager = $storeManager;
        parent::__construct($context, $autocomplete);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|null
     */
	public function execute()
    {
        if($this->bxHelperData->isAutocompleteEnabled()){
            if (!$this->getRequest()->getParam('q', false)) {
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_url->getBaseUrl());
                return $resultRedirect;
            }

            $p13n = $this->_objectManager->create("\Boxalino\Intelligence\Helper\P13n\Adapter");

            $autocomplete = new \Boxalino\Intelligence\Helper\Autocomplete($this->storeManager);
            $responseData = $p13n->autocomplete($this->getRequest()->getParam('q', false), $autocomplete);

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseData);
            return $resultJson;
        }
        return null;
	}
}
