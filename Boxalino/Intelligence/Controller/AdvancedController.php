<?php
namespace Boxalino\Intelligence\Controller;
use Magento\Catalog\Model\Layer\Resolver;
use Boxalino\Intelligence\Model\Advanced as ModelAdvanced;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Boxalino\Intelligence\Helper\P13n\Config;
use Boxalino\Intelligence\Helper\P13n\Sort;
use Boxalino\Intelligence\Helper\Data;
class AdvancedController extends \Magento\CatalogSearch\Controller\Advanced\Result
{
    protected $scopeConfig;
    protected $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    protected $_catalogSearchAdvanced;
    protected $_urlFactory;
    protected $bxHelperData;
    protected $objectManager;
    protected $p13nAdapter;
    public function __construct(
        Context $context,
        ModelAdvanced $catalogSearchAdvanced,
        UrlFactory $urlFactory,
        ScopeConfigInterface $scopeConfigInterface,
        ObjectManager $objectManager,
        Data $bxHelperData,
        \Boxalino\Intelligence\Helper\P13n\Adapter $p13nAdapter
    )
    {
        $this->objectManager = $objectManager;
        $this->bxHelperData = $bxHelperData;
        $this->_catalogSearchAdvanced = $catalogSearchAdvanced;
        $this->_urlFactory = $urlFactory;
        $this->scopeConfig = $scopeConfigInterface;
        $this->p13nAdapter = $p13nAdapter;
        parent::__construct($context, $catalogSearchAdvanced, $urlFactory);
    }

    public function execute()
    {
        if (!$this->bxHelperData->isSearchEnabled()) {
            parent::execute();
        }

        $this->_view->loadLayout();
        $params = $this->getRequest()->getParams();

        $tmp = $this->_catalogSearchAdvanced;

        foreach ($tmp->getAttributes() as $at) {
            $queryAttribute[$at->getStoreLabel()] = $at->getAttributeCode();
        }

        $criteria = $tmp->getSearchCriterias();
        //unset($tmp);
        $lang = substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);

        //setUp Boxalino
        $storeConfig = $this->scopeConfig->getValue('Boxalino_General/general',$this->scopeStore);

        $limit = 1000; //$generalConfig['advanced_search_limit'] == 0 ? 1000 : $generalConfig['advanced_search_limit'];

        //add filters

        $skip = array('name');

        foreach ($params as $key => $value) {

            if (isset($value['from']) || isset($value['to'])) {
                $from = null;
                $to = null;

                if (isset($params[$key]['from']) && $params[$key]['from'] != '' ) {
                    $from = $params[$key]['from'];
                }
                if (isset($params[$key]['to']) && $params[$key]['to'] != '' ) {
                    $to = $params[$key]['to'];
                }

                $skip[] = $key;

                if ($from == null && $to == null) {
                    continue;
                }
                $this->p13nAdapter->addFilterFromTo($key, $from, $to);

            }
        }
        foreach ($criteria as $criterium) {

            $name = $this->bxHelperData->sanitizeFieldName($queryAttribute[$criterium['name']]);

            if (in_array($name, $skip)) {
                continue;
            }

            $values = explode(", ", $criterium['value']);

            $this->p13nAdapter->addFilter($name, $values, null);
        }

        //get result from boxalino
        $this->p13nAdapter->search($params['name'], 0, $limit);
        $entity_ids = $this->p13nAdapter->getEntitiesIds();

        try {
//            Boxalino_CemSearch_Model_Logger::saveFrontActions('AdvancedController_ResultAction', 'storing catalogsearch/advanced for entities with id: ' . implode(', ', $entity_ids));
            $this->_catalogSearchAdvanced->addFilters($params, $entity_ids);
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $defaultUrl = $this->_urlFactory->create()
                ->addQueryParams($this->getRequest()->getQueryValue())
                ->getUrl('*/*/');
            $this->getResponse()->setRedirect($this->_redirect->error($defaultUrl));
        }

        $this->_view->renderLayout();
    }



}
