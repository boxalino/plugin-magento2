<?php

class Boxalino_CemSearch_Block_Script extends Boxalino_CemSearch_Block_Abstract
{
    public function isEnabled()
    {
        return (bool)Mage::getStoreConfig('Boxalino_General/tracker/enabled');
    }

    public function getScripts()
    {
        $html = '';
        $session = Mage::getSingleton('Boxalino_CemSearch_Model_Session');
        $scripts = $session->getScripts(false);

        foreach ($scripts as $script) {
            $html .= $script;
        }
        $session->clearScripts();

        return $html;
    }

    public function isSearch()
    {
        $current = $this->getRequest()->getRouteName() . '/' . $this->getRequest()->getControllerName();
        return $current == 'catalogsearch/result';
    }
}
