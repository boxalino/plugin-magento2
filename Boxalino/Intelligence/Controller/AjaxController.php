<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogSearch
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog Search Controller
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @module     Catalog
 */

namespace Boxalino\Intelligence\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Search\Model\AutocompleteInterface;
use Magento\Framework\Controller\ResultFactory;

class AjaxController extends \Magento\Search\Controller\Ajax\Suggest
{

    protected $bxHelperData;
	public function __construct(
        Context $context,
        \Boxalino\Intelligence\Helper\Data $bxHelperData,
        AutocompleteInterface $autocomplete
    ) {
        $this->bxHelperData = $bxHelperData;
        parent::__construct($context, $autocomplete);
    }
	
	public function execute()
    {
        if($this->bxHelperData->isAutocompleteEnabled()){
            if (!$this->getRequest()->getParam('q', false)) {
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setUrl($this->_url->getBaseUrl());
                return $resultRedirect;
            }

            $p13n = $this->_objectManager->create("\Boxalino\Intelligence\Helper\P13n\Adapter");

            $autocomplete = new \Boxalino\Intelligence\Helper\Autocomplete();
            $responseData = $p13n->autocomplete($this->getRequest()->getParam('q', false), $autocomplete);

            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData($responseData);
            return $resultJson;
        }
	}
}
