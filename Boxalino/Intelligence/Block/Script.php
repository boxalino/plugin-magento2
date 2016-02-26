<?php
namespace Boxalino\Intelligence\Block;

class Script extends \Magento\Framework\View\Element\Template
{
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $bxHelperData;
    protected $customerSession;
    protected $bxAutoCompleteHelper;
    public static $SCRIPT_SESSION = null;

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
    public function isSearch()
    {
        return 'catalogsearch/result' == $this->getRequest()->getRouteName() . '/' . $this->getRequest()->getControllerName();
    }

    public function getBxHelperData(){
        return $this->bxHelperData;
    }

    public function getAutocompleteHelper(){
        return $this->bxAutoCompleteHelper;
    }

    public function getAccount(){
        return $this->_scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
    }
}

