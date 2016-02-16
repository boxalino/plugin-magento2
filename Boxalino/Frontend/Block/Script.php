<?php
namespace Boxalino\Frontend\Block;

class Script extends \Magento\Framework\View\Element\Template
{
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $bxSession;
    protected $helperData;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Frontend\Helper\Data $helperData,
        \Boxalino\Frontend\Model\Session $bxSession,
        array $data = []
        )
    {
        $this->helperData = $helperData;
        $this->bxSession = $bxSession;
        parent::__construct($context, $data);
    }

    public function getScripts()
    {
        $html = '';
        $scripts = $this->bxSession->getScripts();

        foreach ($scripts as $script) {
            $html .= $script;
        }
        $this->bxSession->clearScripts();

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

