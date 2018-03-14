<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class LandingPage
 * @package Boxalino\Intelligence\Block
 */
class LandingPage extends \Magento\Framework\View\Element\Template{

  private $bxHelperData;
  private $p13nHelper;

  public function __construct(
      \Magento\Framework\View\Element\Template\Context $context,
      \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
      \Boxalino\Intelligence\Helper\Data $bxHelperData,
      array $data = []
  ){

      parent::__construct($context, $data);
      $this->p13nHelper = $p13nHelper;
      $this->bxHelperData = $bxHelperData;

  }

  public function isActive(){

    $this->getOtherParams();
    $this->p13nHelper->setLandingPageChoiceId($this->_data['choiceID']);
    return $this->bxHelperData->isPluginEnabled();

  }

  public function getOtherParams(){

    if (!empty($this->_data['bxOtherParams'])) {

      $params = explode(',', $this->_data['bxOtherParams']);

      foreach ($params as $param) {

        $kv = explode('=', $param);

        $extraParams[$kv[0]] = $kv[1];

      }

    $this->p13nHelper->getLandingpageContextParameters($extraParams);

    }

  }

  public function getAssetUrl($asset){

    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $assetRepository = $objectManager ->get('Magento\Framework\View\Asset\Repository');
    return $assetRepository->createAsset($asset)->getUrl();

  }

}
