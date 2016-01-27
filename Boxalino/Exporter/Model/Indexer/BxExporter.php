<?php

namespace Boxalino\Exporter\Model\Indexer;
use Magento\Indexer\Model\Indexer;
use Magento\Framework\App\Config\ScopeConfigInterface;
class BxExporter implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface{

    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
       $this->scopeConfig = $scopeConfig;
    }

    public function executeRow($id){
        echo "executeRow";
        exit;
    }

    public function executeList(array $ids){
        echo "executeList";
        exit;
    }

    public function executeFull(){
		var_dump($this->scopeConfig->getValue('sleekaccordian/parameters/slide_speed', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        exit;
    }

    public function execute($ids){
        echo "execute";
        exit;
    }
}