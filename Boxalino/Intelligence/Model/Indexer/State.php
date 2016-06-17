<?php
namespace Boxalino\Intelligence\Model\Indexer;

use Magento\Indexer\Model\Indexer\State as Mage_State;

/**
 * Class State
 * @package Boxalino\Intelligence\Model\Indexer
 */
class State extends Mage_State{

    /**
     * @var null
     */
    protected STATIC $resetDate = null;

    /**
     * @var bool
     */
    protected STATIC $reset = false;

    /**
     * @param $bool
     */
    public function setReset($bool){
        self::$reset = $bool;
    }

    /**
     * @return null
     */
    public function getResetDate(){
        return self::$resetDate;
    }

    /**
     * @return $this
     */
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