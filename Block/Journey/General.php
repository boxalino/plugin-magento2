<?php
namespace Boxalino\Intelligence\Block\Journey;

/**
 * Class General
 * Defines default desired structure/behavior for CPOJourney interface
 * To be extended by child classes requiring the behavior
 *
 * @package Boxalino\Intelligence\Block\Journey
 */
class General extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Boxalino\Intelligence\Block\BxJourney
     */
    protected $bxJourney;

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * Text constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Block\BxJourney $journey,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_logger = $context->getLogger();
        $this->bxJourney = $journey;
        $this->p13nHelper = $p13nHelper;
    }

    public function getSubRenderings()
    {
        $elements = [];
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

    public function getp13nHelper()
    {
        return $this->p13nHelper;
    }

    public function getRequestUuid()
    {
        return $this->getp13nHelper()->getRequestUuid();
    }

    public function getRequestGroupBy()
    {
        $this->getp13nHelper()->getRequestGroupBy();
    }
}
