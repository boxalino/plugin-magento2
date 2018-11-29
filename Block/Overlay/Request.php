<?php
namespace Boxalino\Intelligence\Block\Overlay;

/**
 * Class OverlayRequest
 * @package Boxalino\Intelligence\Block
 */
class Request extends \Magento\Framework\View\Element\Template{

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    private $p13nHelper;

    /**
     * SearchMessage constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->p13nHelper = $p13nHelper;
    }

    public function sendParametersWithRequest()
    {
        $this->p13nHelper->sendOverlayRequestWithParams();
    }

}
