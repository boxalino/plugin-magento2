<?php
namespace Boxalino\Frontend\Model;
class Session extends \Magento\Framework\Session\Storage
{
    public function addScript($script)
    {

        print_r($script);
//        exit;
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
