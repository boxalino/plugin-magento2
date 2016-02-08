<?php
namespace Boxalino\Frontend\Controller;
use Magento\Catalog\Model\Layer\Resolver;
use Boxalino\Frontend\Model\Advanced as ModelAdvanced;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\ObjectManager\ObjectManager;
use Boxalino\Frontend\Helper\P13n\Config;
use Boxalino\Frontend\Helper\P13n\Sort;
use Boxalino\Frontend\Helper\Data;
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
        \Boxalino\Frontend\Helper\P13n\Adapter $p13nAdapter
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
        if ($this->scopeConfig->getValue('Boxalino_General/general/enabled') == 0) {
            parent::execute();
        }
//            $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
        $this->_view->loadLayout();
        $params = $this->getRequest()->getParams();

        $tmp = $this->_catalogSearchAdvanced;
//
        foreach ($tmp->getAttributes() as $at) {
            $queryAttribute[$at->getStoreLabel()] = $at->getAttributeCode();
        }
//
        $criteria = $tmp->getSearchCriterias();
        //unset($tmp);
        $lang = substr($this->scopeConfig->getValue('general/locale/code',$this->scopeStore), 0, 2);

        //setUp Boxalino
        $storeConfig = $this->scopeConfig->getValue('Boxalino_General/general',$this->scopeStore);
        $generalConfig = $this->scopeConfig->getValue('Boxalino_General/search',$this->scopeStore);

        $p13nConfig = new Config(
            $storeConfig['host'],
            $this->bxHelperData->getAccount(),
            $storeConfig['p13n_username'],
            $storeConfig['p13n_password'],
            $storeConfig['domain']
        );

        $p13nSort = new Sort();
        $this->p13nAdapter->setConfig($p13nConfig);

        $limit = $generalConfig['advanced_search_limit'] == 0 ? 1000 : $generalConfig['advanced_search_limit'];

        //setup search
        $this->p13nAdapter->setupInquiry(
            $generalConfig['advanced_search'],
            $params['name'],
            $lang,
            array($generalConfig['entity_id'], 'discountedPrice', 'title_' . $lang, 'score'),
            $p13nSort,
            0, $limit
        );
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

                if ($key == 'price') {
                    $key = 'discountedPrice';
                }

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

            if ($name == 'description') {
                $name = 'body';
            } else {
                $name = 'products_' . $name;
            }

            $this->p13nAdapter->addFilter($name, $values, null);
        }

        //get result from boxalino
        $this->p13nAdapter->search();
        $this->p13nAdapter->prepareAdditionalDataFromP13n();
        $entity_ids = $this->p13nAdapter->getEntitiesIds();
        unset($p13n);

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
