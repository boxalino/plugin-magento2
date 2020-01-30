<?php
namespace Boxalino\Intelligence\Block;

/**
 * Class BxJourneyAbstract
 * @package Boxalino\Intelligence\Block
 */
abstract class BxJourneyAbstract extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Boxalino\Intelligence\Helper\P13n\Adapter
     */
    protected $p13nHelper;

    /**
     * @var \Boxalino\Intelligence\Helper\Data
     */
    protected $bxHelperData;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $elementConfig = [];

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var DataObjectFactory
     */
    protected $objectFactory;

    /**
     * BxJourney constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper
     * @param \Boxalino\Intelligence\Helper\Data $bxHelperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nHelper,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        \Magento\Framework\DataObjectFactory $objectFactory,
        array $data = []
    ) {
        $this->objectFactory = $objectFactory;
        $this->_request = $context->getRequest();
        $this->_layout = $context->getLayout();
        $this->_logger = $context->getLogger();
        $this->p13nHelper = $p13nHelper;
        $this->bxHelperData = $bxHelperData;

        parent::__construct($context, $data);
    }

    protected function createBlock($type, $name, $data, $arguments, $children)
    {
        $block = $this->getLayout()->createBlock($type, $name, ['data' => $data]);
        foreach ($arguments as $command => $argument) {
            $block->setData($command, $argument);
            if (strpos($command, 'magento_block_function_') === 0) {
                $function = substr($command, strlen('magento_block_function_'));
                foreach ($argument as $value) {
                    $args = [];
                    if ($function == 'setData') {
                        $args = json_decode($value, true);
                        call_user_func(array($block, $function), $args);
                    } else {
                        if ($function == 'setChild') {
                            if(!isset($children[$value])) continue;
                            $args[] = $value;
                            $args[] = $children[$value];
                        } else {
                            $args[] = $value;
                        }
                        call_user_func_array(array($block, $function), $args);
                    }
                }
            }
        }
        return $block;
    }

    protected function createChildrenBlocks($visualElements, $childNames) {
        $children = [];
        foreach ($visualElements as $visualElement) {
            foreach($visualElement['visualElement']['parameters'] as $parameter) {
                if($parameter['name'] == 'magento_block_name' && in_array(reset($parameter['values']), $childNames)) {
                    $children[reset($parameter['values'])] = $this->createBlockElement($visualElement['visualElement']);
                    break;
                }
            }
        }
        return $children;
    }


    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    protected function getDecodedValues($values)
    {
        if(is_array($values)) {
            foreach ($values as $i => $value) {
                if($this->isJson($value)) {
                    $values[$i] = json_decode($value, true);
                }
            }
        }
        return $values;
    }

    protected function createBlockElement($visualElement, $additional_parameter = null)
    {
        $parameters = $visualElement['parameters'];
        $children = [];
        $arguments = [];
        $type = '';
        $name = '';
        $data = ['bxVisualElement' => $visualElement];
        foreach ($parameters as $parameter) {
            if($parameter['name'] == 'magento_block_type') {
                $type = reset($parameter['values']);
            } else if($parameter['name'] == 'magento_block_name') {
                $name = reset($parameter['values']);
            } else {
                if($parameter['name'] == 'magento_block_function_setChild'){
                    $visualElements = $visualElement['subRenderings'][0]['rendering']['visualElements'];
                    $children = $this->createChildrenBlocks($visualElements, $parameter['values']);
                }
                $paramValues = $this->getDecodedValues($parameter['values']);
                if (strpos($parameter['name'], 'magento_block_function_') !== 0) {
                    $paramValues = sizeof($paramValues) < 2 ? reset($paramValues) : $paramValues;
                }
                $arguments[$parameter['name']] = $paramValues;
            }
        }
        if(is_array($additional_parameter)) {
            $data = array_merge($data, $additional_parameter);
        }
        return $this->createBlock($type, $name, $data, $arguments, $children);
    }

    public function createVisualElement($visualElement, $additional_parameter = null) {
        return $this->createBlockElement($visualElement, $additional_parameter);
    }

    public function checkVisualElementForParameter($visualElement, $key, $value) {
        foreach ($visualElement['parameters'] as $parameter) {
            if($parameter['name'] == $key && in_array($value, $parameter['values'])) {
                return true;
            }
        }
        return false;
    }

    protected function getAssetUrl($asset){
        return $this->_assetRepo->createAsset($asset)->getUrl();
    }

    protected function getDependencyElement($url, $type) {
        $element = '';
        if($type == 'css'){
            $element = "<link href=\"{$url}\" type=\"text/css\" rel=\"stylesheet\" />";
        } else if($type == 'js') {
            $element = "<script src=\"{$url}\" type=\"text/javascript\"></script>";
        }
        return $element;
    }

    public function buildHTMLByDependencies($dependencies)
    {
        $html = "";
        if (isset($dependencies['js'])) {
            foreach ($dependencies['js'] as $js) {
                $url = $js;
                if (strpos($url, '::') !== false) {
                    $url = $this->getAssetUrl($url);
                }
                $html .= $this->getDependencyElement($url, 'js');
            }
        }
        if (isset($dependencies['css'])) {
            foreach ($dependencies['css'] as $css) {
                $url = $css;
                if (strpos($url, '::') !== false) {
                    $url = $this->getAssetUrl($url);
                }
                $html .= $this->getDependencyElement($url, 'css');
            }
        }

        return $html;
    }

    abstract function renderDependencies();

    abstract function renderElements();

}
