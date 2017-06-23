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
     * @var \Boxalino\Intelligence\Helper\Autocomplete
     */
    protected $autocompleteHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * AjaxPlugin constructor.
     * @param Context $context
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Boxalino\Intelligence\Helper\Autocomplete $autocompleteHelper
     */
    public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Psr\Log\LoggerInterface $logger,
        \Boxalino\Intelligence\Helper\Autocomplete $autocompleteHelper
    )
    {
        $this->logger = $logger;
        $this->autocompleteHelper = $autocompleteHelper;
        $this->request = $context->getRequest();
        $this->p13nHelper = $p13nHelper;
        $this->resultFactory = $context->getResultFactory();
        $this->bxHelperData = $bxHelperData;
    }

    /**
     * @param \Magento\Search\Controller\Ajax\Suggest $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function aroundExecute(\Magento\Search\Controller\Ajax\Suggest $subject, callable $proceed){

        $query = $this->request->getParam('q', false);
        try{
            if($this->bxHelperData->isAutocompleteEnabled() && $query !== false){
                $responseData = $this->p13nHelper->autocomplete($query, $this->autocompleteHelper);
                /** @var \Magento\Framework\Controller\Result\Json $resultJson */
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData($responseData);
                return $resultJson;
            }
        }catch(\Exception $e) {
            $this->logger->critical($e);
        }
        return $proceed();
    }
}
