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
     * @var \Magento\Catalog\Block\Product\AbstractProduct
     */
    protected $abstractProduct;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

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
     * AjaxPlugin constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param AutocompleteInterface $autocomplete
     * @param \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct
     * @param \Magento\Catalog\Model\Product $productModel
     */
    public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        AutocompleteInterface $autocomplete,
        \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct,
        \Magento\Catalog\Model\Product $productModel
    )
    {
        $this->url =  $context->getUrl();
        $this->request = $context->getRequest();
        $this->p13nHelper = $p13nHelper;
        $this->resultFactory = $context->getResultFactory();
        $this->productModel = $productModel;
        $this->abstractProduct = $abstractProduct;
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
            
            $autocomplete = new \Boxalino\Intelligence\Helper\Autocomplete($this->abstractProduct, $this->productModel);
            $responseData = $this->p13nHelper->autocomplete($query, $autocomplete);

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseData);
            return $resultJson;
        }
        return null;
    }
}
