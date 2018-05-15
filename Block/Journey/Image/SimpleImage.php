<?php

namespace Boxalino\Intelligence\Block\Journey\Image;

/**
 * Class SimpleImage
 * @package Boxalino\Intelligence\Block\Journey\Image
 */
class SimpleImage extends \Magento\Framework\View\Element\Template implements \Boxalino\Intelligence\Block\Journey\CPOJourney{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * SimpleImage constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Block\BxJourney $journey
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_logger = $context->getLogger();
        $this->bxJourney = $journey;
    }

    public function getSubRenderings()
    {
        $elements = array();
        $element = $this->getData('bxVisualElement');
        if(isset($element['subRenderings'][0]['rendering']['visualElements'])) {
            $elements = $element['subRenderings'][0]['rendering']['visualElements'];
        }
        return $elements;
    }

    public function renderVisualElement($element, $additional_parameter = null)
    {
        return $this->bxJourney->createVisualElement($element, $additional_parameter)->toHtml();
    }

    public function getLocalizedValue($values) {
        return $this->p13nHelper->getResponse()->getLocalizedValue($values);
    }
}
