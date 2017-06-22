<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class Script
 * @package Boxalino\Intelligence\Block
 */
class Script extends \Magento\Framework\View\Element\Template{
    
    /**
     * @var string
     */
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Boxalino\Intelligence\Helper\Autocomplete
     */
    protected $bxAutoCompleteHelper;

    /**
     * Script constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param \Boxalino\Intelligence\Helper\Autocomplete $bxAutoCompleteHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Boxalino\Intelligence\Helper\Autocomplete $bxAutoCompleteHelper,
        array $data = []
    )
    {
        $this->customerSession = $customerSession;
        $this->bxHelperData = $bxHelperData;
        $this->bxAutoCompleteHelper = $bxAutoCompleteHelper;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getScripts()
    {
        $html = '';
        foreach($this->bxHelperData->getScripts() as $script) {
            $html.= $script;
        }
        if($this->customerSession->getCustomerId()) {
            $html .= $this->bxHelperData->reportLogin($this->customerSession->getCustomerId());
        }

        return $html;
    }

    /**
     * @return bool
     */
    public function isSearch()
    {
        return 'catalogsearch/result' == $this->getRequest()->getRouteName() . '/' . $this->getRequest()->getControllerName();
    }

    /**
     * @return \Boxalino\Intelligence\Helper\Data
     */
    public function getBxHelperData(){
        
        return $this->bxHelperData;
    }

    /**
     * @return \Boxalino\Intelligence\Helper\Autocomplete
     */
    public function getAutocompleteHelper(){
        
        return $this->bxAutoCompleteHelper;
    }

    /**
     * @return mixed
     */
    public function getAccount(){
        
        return $this->_scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
    }
}

