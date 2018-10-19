<?php
namespace Boxalino\Intelligence\Block\Journey;

/**
 * Class Text
 * @package Boxalino\Intelligence\Block
 */
class Text extends General implements CPOJourney
{

    public function getAttributes() {
        $attributes = $this->getData('attributes');
        if(is_array($attributes) && isset($attributes['href'])) {
            $link = $this->getAssetUrl($attributes['href']);
            $attributes['href'] = $link;
        }
        return $attributes;
    }

    public function getAssetUrl($asset) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $assetRepository = $objectManager ->get('Magento\Framework\View\Asset\Repository');
        return $assetRepository->createAsset($asset)->getUrl();
    }
}
