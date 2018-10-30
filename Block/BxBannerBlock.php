<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BxRecommendationBlock
 * @package Boxalino\Intelligence\Block
 */
Class BxBannerBlock extends BxRecommendationBlock implements \Magento\Framework\DataObject\IdentityInterface{

    protected $_logger;

    protected $bannerLayout = null;

    protected $bannerLayoutCssClass = null;

    protected $bannerSlidesCssClass = null;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\Link\Product\CollectionFactory $factory,
        \Magento\Framework\App\Request\Http $request,
        array $data
    ){
        $this->_logger = $context->getLogger();
        parent::__construct($context,
            $p13nHelper,
            $bxHelperData,
            $checkoutSession,
            $catalogProductVisibility,
            $factory,
            $request,
            $data);

    }

    protected function _prepareData()
    {
        return $this;
    }

    protected function prepareRecommendations($recommendations = array(), $returnFields = array())
    {
        parent::prepareRecommendations(
            $recommendations,
            array(
                'title',
                'products_bxi_bxi_jssor_slide',
                'products_bxi_bxi_jssor_transition',
                'products_bxi_bxi_name',
                'products_bxi_bxi_jssor_control',
                'products_bxi_bxi_jssor_break'
            )
        );
    }

    public function isActive()
    {
        if ($this->bxHelperData->isBannerEnabled()) {
            return true;
        }

        return false;
    }

    public function check()
    {
        try{
            $values = array(
                0 => $this->getBannerSlides(),
                1 => $this->getBannerJssorId(),
                2 => $this->getBannerJssorSlideTransitions(),
                3 => $this->getBannerJssorSlideBreaks(),
                4 => $this->getBannerJssorSlideControls(),
                5 => $this->getBannerJssorOptions(),
                6 => $this->getBannerJssorMaxWidth(),
                7 => $this->getBannerJssorCSS(),
                8 => $this->getBannerJssorStyle(),
                9 => $this->getBannerJssorLoadingScreen(),
                10 => $this->getBannerJssorSlidesStyle(),
                11 => $this->getBannerJssorBulletNavigator(),
                12 => $this->getBannerJssorArrowNavigator(),
                13 => $this->getBannerFunction(),
                14 => $this->getBannerLayout()
            );

            if (!in_array('', $values)) {
                return true;
            }else{
                foreach ($values as $key => $value) {
                    try{
                        if($value == '') {
                            throw new \Exception("Function $key returned empty.");
                        }
                    } catch(\Exception $e){
                        $this->setFallback(true);
                        $this->_logger->critical($e);
                    }
                }
                return false;
            }
        } catch (\Exception $e) {
            $this->setFallback(true);
            $this->_logger->critical($e);
        }
    }

    public function getBannerSlides()
    {
        $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array('products_bxi_bxi_jssor_slide', 'products_bxi_bxi_name'), $this->_data['widget']);
        $counters = array();
        foreach($slides as $id => $vals) {
            $slides[$id]['div'] = $this->getBannerSlide($id, $vals, $counters);
        }

        // if the small banner is used, use the first banner for the first block & the second for the second
        if ($this->getBannerLayout() != 'large') {
            if ($this->getIndex() == '1') {
                return array(reset($slides));
            }
            if ($this->getIndex() == '2') {
                return array(end($slides));
            }
        }

        return $slides;
    }

    public function getBannerSlide($id, $vals, &$counters)
    {
        $language = $this->p13nHelper->getLanguage();
        if(isset($vals['products_bxi_bxi_jssor_slide']) && sizeof($vals['products_bxi_bxi_jssor_slide']) > 0) {
            $json = $vals['products_bxi_bxi_jssor_slide'][0];

            $slide = json_decode($json, true);
            if(isset($slide[$language])) {
                $json = $slide[$language];

                // add configId as prefix for the classes
                $json = $this->addPrefixToClasses($json);
                for($i=1; $i<10; $i++) {
                    if(!isset($counters[$i])) {
                        $counters[$i] = 0;
                    }
                    $pieces = explode('BX_COUNTER'.$i, $json);
                    foreach($pieces as $j => $piece) {
                        if($j >= sizeof($pieces)-1) {
                            continue;
                        }
                        $pieces[$j] .= $counters[$i]++;
                    }
                    $json = implode('', $pieces);

                }
                return $json;
            }
        }
        return '';
    }

    public function getBannerJssorSlideGenericJS($key)
    {
        $language = $this->p13nHelper->getLanguage();
        $slides = $this->p13nHelper->getClientResponse()->getHitFieldValues(array($key), $this->_data['widget']);

        $jsArray = array();
        foreach($slides as $id => $vals) {
            if(isset($vals[$key]) && sizeof($vals[$key]) > 0) {

                $jsons = json_decode($vals[$key][0], true);
                if(isset($jsons[$language])) {
                    $json = $jsons[$language];
                    //fix some special case an extra '}' appears wrongly at the end
                    $minus = 2;
                    if(substr($json, strlen($json)-1, 1) == '}') {
                        $minus = 3;
                    }
                    //removing the extra [] around
                    $json = substr($json, 1, strlen($json)-$minus);
                    $jsArray[] = $json;
                }
            }
        }
        return '[' . implode(',', $jsArray) . ']';
    }

    public function addPrefixToClasses($json)
    {
        $idFromConfig = $this->getIdFromConfig();
        $elems = explode(' ' ,$json);
        foreach ($elems as $i => $value) {
            if (preg_match('/bxBanner/', $value)) {
                $value = str_replace('bxBanner', $idFromConfig . '_bxBanner', $value);
                $elems[$i] = $value;
            }
        }

        $json = implode(' ', $elems);
        return $json;

    }

    public function getBannerJssorSlideTransitions()
    {
        return $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_transition');
    }

    public function getBannerJssorSlideBreaks()
    {
        return $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_break');
    }

    public function getBannerJssorSlideControls()
    {
        return $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_control');
    }

    public function getBannerJssorOptions()
    {
        $bannerJssorOptions = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_options', '', $this->_data['widget']);
        // replace id from Intelligence with id from block configuration
        $bannerJssorOptions = str_replace($this->getBannerJssorId(), $this->getIdFromConfig(), $bannerJssorOptions);

        return $bannerJssorOptions;
    }

    public function getBannerJssorId()
    {
        $bannerJssorId = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_id', '', $this->_data['widget']);
        return $bannerJssorId;
    }

    public function getBannerJssorStyle()
    {
        $bannerJssorStyle = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_style', '', $this->_data['widget']);
        return $bannerJssorStyle;
    }

    public function getBannerJssorSlidesStyle()
    {
        $bannerJssorrSlidesStyle = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_slides_style', '', $this->_data['widget']);
        return $bannerJssorrSlidesStyle;
    }

    public function getBannerJssorMaxWidth()
    {
        $bannerJssorMaxWidth = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_max_width', '', $this->_data['widget']);
        return $bannerJssorMaxWidth;
    }

    public function getBannerJssorCSS()
    {
        $bannerJssorCss = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_css', '', $this->_data['widget']);
        // replace id from Intelligence with id from block configuration
        $bannerJssorCss = str_replace($this->getBannerJssorId(), $this->getIdFromConfig(), $bannerJssorCss);

        return str_replace("JSSORID", $this->getBannerJssorId(), $bannerJssorCss);
    }

    public function getBannerJssorLoadingScreen()
    {
        $bannerJssorLoadingScreen = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_loading_screen', '', $this->_data['widget']);
        return $bannerJssorLoadingScreen;
    }

    public function getBannerJssorBulletNavigator()
    {
        $bannerJssorBulletNavigator = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_bullet_navigator', '', $this->_data['widget']);
        return $bannerJssorBulletNavigator;
    }

    public function getBannerJssorArrowNavigator()
    {
        $bannerJssorArrowNavigator = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_arrow_navigator', '', $this->_data['widget']);
        return $bannerJssorArrowNavigator;
    }

    public function getBannerFunction()
    {
        $bannerFunction = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_function', '', $this->_data['widget']);
        // replace id from Intelligence with id from block configuration
        $bannerFunction = str_replace($this->getBannerJssorId(), $this->getIdFromConfig(), $bannerFunction);

        return $bannerFunction;
    }

    public function getBannerLayout()
    {
        if(is_null($this->bannerLayout))
        {
            $this->bannerLayout = $this->p13nHelper->getClientResponse()->getExtraInfo('banner_jssor_layout', '', $this->_data['widget']);
        }
        return $this->bannerLayout;
    }

    public function getBannerTitle()
    {
        $bannerTitle = $this->p13nHelper->getClientResponse()->getResultTitle($this->_data['widget']);
        return $bannerTitle;
    }

    public function getHitCount()
    {
        $hitCount = sizeof($this->p13nHelper->getClientResponse()->getHitIds($this->_data['widget']));
        return $hitCount;
    }

    public function getIdFromConfig()
    {
        $jssorConfigId = $this->getData('jssorID');

        if($jssorConfigId == "") {
            return $this->getBannerJssorId();
        }

        return $jssorConfigId;
    }

    public function getIndex()
    {
        $jssorIndex = $this->getData('jssorIndex');

        return $jssorIndex;
    }

    public function getOverlayValues($key)
    {
        return $this->p13nHelper->getOverlayValues($key, $this->_data['widget']);
    }

    public function getOverlayTimeout()
    {
        //$timeout is the time in seconds (e.g. 3), has to be multiplied by 1000 (milliseconds) for js function 'setTimeout'
        $timeout = $this->getOverlayValues('bx_extend_timeout');

        if ($timeout) {
            return ($timeout * 1000);
        }else{
            return 5000;
        }
    }

    public function getOverlayExitIntendTimeout()
    {
        $timeout = $this->getOverlayValues('bx_extend_exit_intend_timeout');
        if (!empty($timeout)) {
            return $timeout;
        }else{
            return 5;
        }
    }

    public function getOverlayFrequency()
    {
        $frequency = $this->getOverlayValues('bx_extend_frequency');
        if (!empty($frequency)) {
            return $frequency;
        }else{
            return 0;
        }
    }

    public function getOverlayPosition()
    {
        $position = $this->getOverlayValues('bx_extend_position');
        if ($position) {
            return $position;
        }

        return 'Centre';
    }

    public function withLightboxEffect()
    {
        $withLightbox = $this->getOverlayValues('bx_extend_lightbox');
        if (!empty($withLightbox)) {
        }

        return true;
    }

    public function getBannerCss()
    {
        $bannerLayout = $this->getBannerLayout();
        if(strpos($bannerLayout, 'large')!== false)
        {
            $this->bannerLayoutCssClass = "bxLargeBannerJssor";
            $this->bannerSlidesCssClass = 'bxLargeBannerJssorSlides';

            return;
        }

        $this->bannerLayoutCssClass = "bxSmallBannerJssor";
        $this->bannerSlidesCssClass = "bxSmallBannerJssorSlides";
        return;
    }

    public function getBannerLayoutCssClass()
    {
        if(is_null($this->bannerLayoutCssClass))
        {
            $this->getBannerCss();
        }

        return $this->bannerLayoutCssClass;
    }

    public function getBannerSlidesCssClass()
    {
        if(is_null($this->bannerSlidesCssClass))
        {
            $this->getBannerCss();
        }

        return $this->bannerSlidesCssClass;
    }

}