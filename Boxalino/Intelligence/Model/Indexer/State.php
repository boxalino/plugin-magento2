<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Magento\Indexer\Model\Indexer\State as Mage_State;
class State extends Mage_State{
    
    protected STATIC $resetDate = null;
    protected STATIC $reset = false;
    
    public function setReset($bool){
        self::$reset = $bool;
    }
    
    public function getResetDate(){
        return self::$resetDate;
    }
    
    public function beforeSave()
    {
        if(self::$resetDate == null){
            self::$resetDate = $this->getUpdated();
        }
        $date = self::$resetDate != null && self::$reset ? self::$resetDate : time();
        $this->setUpdated($date);
        return \Magento\Framework\Model\AbstractModel::beforeSave();
    }
}