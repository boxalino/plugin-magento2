<?php
namespace Boxalino\Intelligence\Block\Overlay;

/**
 * Class Block
 * @package Boxalino\Intelligence\Block\Overlay
 */
class Main extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Boxalino\Intelligence\Api\P13nAdapterInterface
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var
     */
    protected $response = null;

    /**
     * @var null | string
     */
    protected $overlayWidgetChoice = null;

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;


    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Api\P13nAdapterInterface $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        array $data = []
    ){
        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $data);

    }

    /**
     * Render the block containing the overlay
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOverlayBlock()
    {
        $parameters = [['name' => 'variant', 'values' => [$this->getVariantIndex()]]];
        $parameters[] = ['name' => 'jsParams', 'values' => [$this->getOverlayJsParameters()]];
        $block = $this->getLayout()->createBlock(
            $this->getBlockPathFromResponse(),
            'bx_overlay',
            ['data' => ['bxVisualElement' => ['parameters' => $parameters], "widget"=> $this->overlayWidgetChoice]]
        )->setTemplate($this->getTemplatePathFromResponse());


        return $block;
    }

    /**
     * Lightbox effect status
     * @return bool
     */
    public function withLightboxEffect()
    {
        return $this->getOverlayValues('bx_extend_lightbox');
    }

    /**
     * Conditions if the overlay can be displayed
     *
     * @return bool
     */
    public function canBeDisplayed()
    {
        return $this->isActive() && $this->getBlockPathFromResponse()!=null && $this->getTemplatePathFromResponse()!=null;
    }

    /**
     * return Varien_Object
     */
    public function getJsParameters()
    {
        return [
            "parameters" => $this->getOverlayJsParameters(),
            "js"=> $this->getOverlayBehaviorJs(),
            "extra" => $this->getOverlayExtraParams(),
            "basketTotal" => $this->getGrandTotal(),
            "language" => $this->getLanguage(),
            "url" => $this->getControllerUrl()
        ];
    }

    public function getOverlayJsParameters()
    {
        return $this->getOverlayValues('bx-extend-parameters');
    }

    public function getOverlayBehaviorJs()
    {
        return $this->getOverlayValues('bx-extend-behaviour');
    }

    /**
     * @return string
     */
    public function getVariantIndex()
    {
        return $this->p13nHelper->getOverlayVariantId();
    }

    /**
     * @ex Boxalino_Intelligence::journey/overlay/simple.phtml
     * @return mixed
     */
    public function getTemplatePathFromResponse()
    {
        return $this->getOverlayValues('bx-template-path');
    }

    /**
     * @ex \Boxalino\Intelligence\Block\Journey\General
     * @return mixed
     */
    public function getBlockPathFromResponse()
    {
        return $this->getOverlayValues('bx-block-path');
    }

    public function getOverlayExtraParams()
    {
        $paramsJson = $this->getOverlayValues('bx-extend-extra-params');
        if (empty($paramsJson)) {
            return 0;
        }
        return $paramsJson;
    }

    /**
     * Overlay values
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function getOverlayValues($key)
    {
        if(is_null($this->overlayWidgetChoice))
        {
            $this->overlayWidgetChoice = $this->p13nHelper->getOverlayChoice();
        }

        if(is_null($this->response))
        {
            $this->response = $this->getResponse();
        }

        return $this->response->getExtraInfo($key, '', $this->overlayWidgetChoice);
    }

    /**
     * Check if the overlays are enabled
     * @return bool
     */
    public function isActive()
    {
        if($this->bxHelperData->isOverlayEnabled() && $this->bxHelperData->isPluginEnabled())
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

    /**
     * Ajax controller url to send request to Boxalino
     * @return string
     */
    public function getControllerUrl()
    {
        return $this->getUrl("bxGenericRecommendations/index/overlayRequest");
    }

    /**
     * chart total for custom business logic
     *
     * @return int
     */
    public function getGrandTotal()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $grandTotal = $cart->getQuote()->getGrandTotal();
        if (empty($grandTotal)) {
            return 0;
        }

        return $grandTotal;
    }

    /**
     * locale
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->bxHelperData->getLanguage();
    }

    /**
     * @return mixed|null
     */
    public function getResponse()
    {
        try{
            if(is_null($this->response))
            {
                $this->response = $this->p13nHelper->getClientResponse();
            }

            return $this->response;
        }  catch(\Exception $e){
            $this->fallback = true;
            $this->_logger->critical($e);
        }

    }

}
