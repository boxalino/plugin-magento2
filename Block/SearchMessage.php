<?php

namespace Boxalino\Intelligence\Block;

/**
 * Class Facets
 * @package Boxalino\Intelligence\Block
 */
class SearchMessage extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    private $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    private $bxHelperData;

    private $bxResponse;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * SearchMessage constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        $this->_logger = $context->getLogger();
    }

    public function isActive()
    {
        if($this->bxHelperData->isPluginEnabled())
        {
            $this->getResponse();
            if($this->fallback)
            {
                return false;
            }

            return true;
        }

        return false;
    }

    public function getResponse()
    {
        try {
            if(is_null($this->bxResponse))
            {
                $this->bxResponse = $this->p13nHelper->getResponse();
            }

            return $this->bxResponse;
        } catch(\Exception $e){
            $this->fallback = true;
            $this->_logger->critical($e);
        }

    }

    public function getFallbackResponse()
    {
        return null;
    }

    public function getDisplayType() {
        return $this->getResponse()->getExtraInfo('search_message_display_type');
    }

    public function getSearchMessageTitle() {
        return $this->getResponse()->getExtraInfoLocalizedValue('search_message_title');
    }

    public function getDescription() {
        return $this->getResponse()->getExtraInfoLocalizedValue('search_message_description');
    }

    /**
     * @deprecated to use css class instead of style
     * @return mixed
     */
    public function getTitleStyle() {
        return $this->getResponse()->getExtraInfoLocalizedValue('search_message_title_style');
    }

    /**
     * @deprecated uses css class instead of style
     * @return mixed
     */
    public function getDescriptionStyle() {
        return $this->getResponse()->getExtraInfoLocalizedValue('search_message_description_style');
    }

    public function getMainImage() {
        return $this->getResponse()->getExtraInfo('search_message_main_image');
    }

    public function getSideImage() {
        return $this->getResponse()->getExtraInfo('search_message_side_image');
    }

    public function getSearchMessageLink() {
        return $this->getResponse()->getExtraInfoLocalizedValue('search_message_link');
    }

    public function getRedirectLink() {
        return $this->getResponse()->getExtraInfoLocalizedValue('redirect_url');
    }

    public function getContainerCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_container_css_class');
    }

    public function getLinkCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_link_css_class');
    }

    public function getSideImageCssClass() {
        if($this->fallback){
            return $this->getFallbackResponse();
        }

        return $this->getResponse()->getExtraInfo('search_message_side_image_css_class');
    }

    public function getGeneralCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_general_css_class');
    }

    public function getMainImageCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_main_image_css_class');
    }

    public function getDescriptionCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_description_css_class');
    }

    public function getTitleCssClass() {
        return $this->getResponse()->getExtraInfo('search_message_title_css_class');
    }

}
