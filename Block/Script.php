<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class Script
 * @package Boxalino\Intelligence\Block
 */
class Script extends \Magento\Framework\View\Element\Template
{

    CONST BXL_INTELLIGENCE_STAGE_SCRIPT="//r-st.bx-cloud.com/static/ba.min.js";
    CONST BXL_INTELLIGENCE_PROD_SCRIPT="//track.bx-cloud.com/static/ba.min.js";
    CONST BXL_INTELLIGENCE_SCRIPT = "//cdn.bx-cloud.com/frontend/rc/js/ba.min.js";
    CONST BXL_INTELLIGENCE_NARRATIVE_PROD_SCRIPT="//track.bx-cloud.com/static/bav2.min.js";
    CONST BXL_INTELLIGENCE_NARRATIVE_STAGE_SCRIPT="//r-st.bx-cloud.com/static/bav2.min.js";

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
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

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
        \Magento\Search\Helper\Data $searchHelper,
        \Boxalino\Intelligence\Helper\Autocomplete $bxAutoCompleteHelper,
        array $data = []
    )
    {
        $this->searchHelper = $searchHelper;
        $this->customerSession = $customerSession;
        $this->bxHelperData = $bxHelperData;
        $this->bxAutoCompleteHelper = $bxAutoCompleteHelper;
        parent::__construct($context, $data);
    }

    public function setTemplate($template)
    {
        if(!$this->bxHelperData->isPluginEnabled()){
            return $this;
        }
        return parent::setTemplate($template);
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

    public function getQueryParamName()
    {
        return $this->searchHelper->getQueryParamName();
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
        
        return $this->_scopeConfig->getValue('bxGeneral/general/account_name', $this->scopeStore);
    }

    /**
     * getting the upgraded script
     * @return string
     */
    public function getBaScriptServerPath()
    {
        $apiKey = $this->_scopeConfig->getValue('bxGeneral/general/apiKey', $this->scopeStore);
        $apiSecret = $this->_scopeConfig->getValue('bxGeneral/general/apiSecret', $this->scopeStore);
        if(empty($apiKey) || empty($apiSecret))
        {
            return self::BXL_INTELLIGENCE_SCRIPT;
        }
        $isDev = $this->_scopeConfig->getValue('bxGeneral/general/dev', $this->scopeStore);
        if($isDev)
        {
            if($this->getBxHelperData()->isNarrativeTrackerEnabled())
            {
                return self::BXL_INTELLIGENCE_NARRATIVE_STAGE_SCRIPT;
            }
            return self::BXL_INTELLIGENCE_STAGE_SCRIPT;
        }

        if($this->getBxHelperData()->isNarrativeTrackerEnabled())
        {
            return self::BXL_INTELLIGENCE_NARRATIVE_PROD_SCRIPT;
        }
        return self::BXL_INTELLIGENCE_PROD_SCRIPT;
    }

}

