<?php

abstract class Boxalino_CemSearch_Block_Abstract extends Mage_Core_Block_Template
{
    public function isPluginEnabled()
    {
        return Mage::helper('Boxalino_CemSearch')->isEnabled();
    }

    public function isPageEnabled($uri)
    {
        return Mage::helper('Boxalino_CemSearch')->isPageEnabled($uri);
    }

    public function getSearchUrl()
    {
        return $this->getUrl('Boxalino_CemSearch/search');
    }

    public function getLanguage()
    {
        return Mage::helper('Boxalino_CemSearch')->getLanguage();
    }

    public function getSuggestUrl()
    {
        return Mage::helper('Boxalino_CemSearch')->getSuggestUrl();
    }

    public function getSuggestParameters()
    {
        return Mage::helper('Boxalino_CemSearch')->getSuggestParameters();
    }


}
