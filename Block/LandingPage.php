<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class LandingPage
 * @package Boxalino\Intelligence\Block
 */
class LandingPage extends \Magento\Framework\View\Element\Template{

  public function isActive(){

      if ($this->bxHelperData->isPluginEnabled()) {

          return true;

      }

  }

}
