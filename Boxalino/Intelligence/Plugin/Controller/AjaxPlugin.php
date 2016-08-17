<?php
namespace Boxalino\Intelligence\Plugin\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class AjaxController
 * @package Boxalino\Intelligence\Controller
 */
class AjaxPlugin{

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;

    /**
     * @var \Boxalino\Intelligence\Helper\Autocomplete
     */
    protected $autocompleteHelper;

    /**
     * AjaxPlugin constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param AutocompleteInterface $autocomplete
     * @param \Boxalino\Intelligence\Helper\Autocomplete $autocompleteHelper
     */
    public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Autocomplete $autocompleteHelper
    )
    {
        $this->autocompleteHelper = $autocompleteHelper;
        $this->url =  $context->getUrl();
        $this->request = $context->getRequest();
        $this->p13nHelper = $p13nHelper;
        $this->resultFactory = $context->getResultFactory();
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|null
     */
    public function aroundExecute(){
        $query = $this->request->getParam('q', false);
        if (!$query) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->url->getBaseUrl());
            return $resultRedirect;
        }

        if($this->bxHelperData->isAutocompleteEnabled()){
            $responseData = $this->p13nHelper->autocomplete($query, $this->autocompleteHelper);

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseData);
            return $resultJson;
        }
        return null;
    }
}
