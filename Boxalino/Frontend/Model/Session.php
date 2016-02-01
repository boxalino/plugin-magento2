<?php

class Boxalino_Frontend_Model_Session extends Mage_Core_Model_Session_Abstract
{

    public function __construct()
    {
        $this->init('checkout');
    }

    public function addScript($script)
    {
        if (!isset($this->_data['scipts']) || !is_array($this->_data['scipts'])) {
            $this->_data['scipts'] = array();
        }
        $this->_data['scipts'][] = $script;
    }

    public function getScripts()
    {
        $scripts = array();
        if (isset($this->_data['scipts']) && is_array($this->_data['scipts'])) {
            $scripts = $this->_data['scipts'];
        }
        return $scripts;
    }

    public function clearScripts()
    {
        $this->_data['scipts'] = array();
    }
}
