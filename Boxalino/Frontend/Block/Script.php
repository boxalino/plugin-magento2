<?php
namespace Boxalino\Frontend\Block;

class Script extends \Magento\Framework\View\Element\Template
{
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $helperData;
    protected $customerSession;

    public static $SCRIPT_SESSION = null;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Boxalino\Frontend\Helper\Data $helperData,
        array $data = []
        )
    {
        $this->customerSession = $customerSession;
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function getScripts()
    {
        $html = '';
        foreach($this->helperData->getScripts() as $script) {
            $html.= $script;
        }
        if($this->customerSession->getCustomerId()) {
            $html .= $this->helperData->reportLogin($this->customerSession->getCustomerId());
        }

        return $html;
    }

    public function isSearch()
    {
        $current = $this->getRequest()->getRouteName() . '/' . $this->getRequest()->getControllerName();
        return $current == 'catalogsearch/result';
    }

    public function getAccount(){
        return $this->_scopeConfig->getValue('bxGeneral/general/account_name',$this->scopeStore);
    }

    public function getBxHelperData(){
        return $this->helperData;
    }
}

