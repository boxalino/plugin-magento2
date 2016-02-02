<?php

namespace Boxalino\Exporter\Helper;

class BxDataIntelligenceXML
{
	protected $account;
	protected $files;
	protected $config;
	protected $bxGeneral;
	
	public function __construct($account, $files, $config) {
		$this->account = $account;
		$this->files = $files;
		$this->config = $config;
		$this->bxGeneral = new BxGeneral();
	}
	
	public function createXML($name, $attributes, $attributesValuesByName, $customer_attributes, $tags = null, $productTags = null) {
		
		$withTag = ($tags != null && $productTags != null) ? true : false;
		$languages = $this->config->getAccountLanguages($this->account);
		
		$xml = new \SimpleXMLElement('<root/>');

        $languagesXML = $xml->addChild('languages');
        $containers = $xml->addChild('containers');

        //languages
        foreach ($languages as $lang) {
            $language = $languagesXML->addChild('language');
            $language->addAttribute('id', $lang);
        }

        //product
        $products = $containers->addChild('container');
        $products->addAttribute('id', 'products');
        $products->addAttribute('type', 'products');

        $sources = $products->addChild('sources');
        //#########################################################################

        //product source
        $source = $sources->addChild('source');
        $source->addAttribute('id', 'item_vals');
        $source->addAttribute('type', 'item_data_file');

        $source->addChild('file')->addAttribute('value', 'products.csv');
        $source->addChild('itemIdColumn')->addAttribute('value', 'entity_id');

        $this->sxml_append_options($source);
        //#########################################################################

        $attrs = array_keys($attributesValuesByName);
        if ($withTag) { //$this->_storeConfig['export_tags'] && 
            $attrs[] = 'tag';
        }

        foreach ($attrs as $attr) {
            if ($attr == 'visibility' || $attr == 'status') {
                continue;
            }
            $attr = $this->bxGeneral->sanitizeFieldName($attr);

            //attribute
            $source = $sources->addChild('source');
            $source->addAttribute('type', 'resource');
            $source->addAttribute('id', 'resource_' . $attr);

            $source->addChild('file')->addAttribute('value', $attr . '.csv');
            $source->addChild('referenceIdColumn')->addAttribute('value', $attr . '_id');
            $source->addChild('itemIdColumn')->addAttribute('value', $attr . '_id');

            $labelColumns = $source->addChild('labelColumns');
            foreach ($languages as $lang) {
                $label = $labelColumns->addChild('language');
                $label->addAttribute('name', $lang);
                $label->addAttribute('value', 'value_' . $lang);
            }

            $this->sxml_append_options($source);

            //product & attribute
            $source = $sources->addChild('source');
            $source->addAttribute('type', 'item_data_file');
            $source->addAttribute('id', 'item_' . $attr);

            $source->addChild('file')->addAttribute('value', 'product_' . $attr . '.csv');
            $source->addChild('itemIdColumn')->addAttribute('value', 'entity_id');

            $this->sxml_append_options($source);

        }

        //########################################################################
        if (true) { //$this->_storeConfig['export_categories']
            //categories
            $sourceCategory = $sources->addChild('source');
            $sourceCategory->addAttribute('type', 'hierarchical');
            $sourceCategory->addAttribute('id', 'resource_categories');


            $sourceCategory->addChild('file')->addAttribute('value', 'categories.csv');
            $sourceCategory->addChild('referenceIdColumn')->addAttribute('value', 'category_id');
            $sourceCategory->addChild('parentIdColumn')->addAttribute('value', 'parent_id');

            $labelColumns = $sourceCategory->addChild('labelColumns');
            foreach ($languages as $lang) {
                $label = $labelColumns->addChild('language');
                $label->addAttribute('name', $lang);
                $label->addAttribute('value', 'value_' . $lang);
            }

            $this->sxml_append_options($sourceCategory);

            //categories & products
            $source = $sources->addChild('source');
            $source->addAttribute('type', 'item_data_file');
            $source->addAttribute('id', 'item_categories');


            $source->addChild('file')->addAttribute('value', 'product_categories.csv');
            $source->addChild('itemIdColumn')->addAttribute('value', 'entity_id');

            $this->sxml_append_options($source);
        }
        //#########################################################################

        //########################################################################
        // IMAGES
        if ($this->config->exportProductImages($this->account)) {

            //categories & products images
            $source = $sources->addChild('source');
            $source->addAttribute('type', 'item_data_file');
            $source->addAttribute('id', 'item_cache_image_url');

            $source->addChild('file')->addAttribute('value', 'product_cache_image_url.csv');
            $source->addChild('itemIdColumn')->addAttribute('value', 'entity_id');

            $this->sxml_append_options($source);
			
            //categories & products images
            $source = $sources->addChild('source');
            $source->addAttribute('type', 'item_data_file');
            $source->addAttribute('id', 'item_cache_image_thumbnail_url');

            $source->addChild('file')->addAttribute('value', 'product_cache_image_thumbnail_url.csv');
            $source->addChild('itemIdColumn')->addAttribute('value', 'entity_id');

            $this->sxml_append_options($source);
        }
        //#########################################################################

        //property
        $properties = $products->addChild('properties');
        $props = $this->prepareProperties($attributes, $attributesValuesByName, $withTag);

        foreach ($props as $prop) {
            if ($prop['id'] == 'entity_id') {

            }

            $property = $properties->addChild('property');
            $property->addAttribute('id', $this->bxGeneral->sanitizeFieldName($prop['id']));
            $property->addAttribute('type', $prop['ptype']);

            $transform = $property->addChild('transform');
            $logic = $transform->addChild('logic');
            $ls = $prop['name'] == null ? 'item_vals' : 'item_' . $prop['name'];
            $logic->addAttribute('source', $this->bxGeneral->sanitizeFieldName($ls));
            $logic->addAttribute('type', $prop['type']);
            if ($prop['has_lang'] == true) {
                foreach ($languages as $lang) {
                    $field = $logic->addChild('field');
                    $field->addAttribute('column', $this->bxGeneral->sanitizeFieldName($prop['field']) . '_' . $lang);
                    $field->addAttribute('language', $lang);
                }
            } else {
                $logic->addChild('field')->addAttribute('column', $this->bxGeneral->sanitizeFieldName($prop['field']));
            }

            $params = $property->addChild('params');
            if ($prop['type'] != 'direct') {
                $params->addChild('referenceSource')->addAttribute('value', 'resource_' . $this->bxGeneral->sanitizeFieldName($prop['reference']));
            }

        }
        //##################################

        //##################################

        if ($this->config->isCustomersExportEnabled($this->account)) {
            $customers = $containers->addChild('container');
            $customers->addAttribute('id', 'customers');
            $customers->addAttribute('type', 'customers');

            $sources = $customers->addChild('sources');
            //#########################################################################

            //customer source
            $source = $sources->addChild('source');
            $source->addAttribute('id', 'customer_vals');
            $source->addAttribute('type', 'item_data_file');

            $source->addChild('file')->addAttribute('value', 'customers.csv');
            $source->addChild('itemIdColumn')->addAttribute('value', 'customer_id');

            $this->sxml_append_options($source);
            //#########################################################################

            $properties = $customers->addChild('properties');
            foreach (
                $customer_attributes as $prop
            ) {
                $type = 'string';
                $column = $prop;
                switch($prop) {
                    case 'id':
                        $type = 'id';
                        $column = 'customer_id';
                        break;
                    case 'dob':
                        $type = 'date';
                        break;
                }

                $property = $properties->addChild('property');
                $property->addAttribute('id', $prop);
                $property->addAttribute('type', $type);

                $transform = $property->addChild('transform');

                $logic = $transform->addChild('logic');
                $logic->addAttribute('source', 'customer_vals');
                $logic->addAttribute('type', 'direct');
                $logic->addChild('field')->addAttribute('column', $column);

                $property->addChild('params');
            }
        }

        if ($this->config->isTransactionsExportEnabled($this->account)) {
            $transactions = $containers->addChild('container');
            $transactions->addAttribute('id', 'transactions');
            $transactions->addAttribute('type', 'transactions');

            $sources = $transactions->addChild('sources');
            //#########################################################################

            //transaction source
            $source = $sources->addChild('source');
            $source->addAttribute('id', 'transactions');
            $source->addAttribute('type', 'transactions');

            $source->addChild('file')->addAttribute('value', 'transactions.csv');
            $source->addChild('orderIdColumn')->addAttribute('value', 'order_id');
            $customerIdColumn = $source->addChild('customerIdColumn');
            $customerIdColumn->addAttribute('value', 'customer_id');
            $customerIdColumn->addAttribute('customer_property_id', 'customer_id');
            // guests are customers that don't sign up and therefore have no customer id or profile
            $customerIdColumn->addAttribute('guest_property_id', 'guest_id');
            $productIdColumn = $source->addChild('productIdColumn');
            $productIdColumn->addAttribute('value', 'entity_id');
            $productIdColumn->addAttribute('product_property_id', 'product_entity_id');
            $source->addChild('productListPriceColumn')->addAttribute('value', 'price');
            $source->addChild('productDiscountedPriceColumn')->addAttribute('value', 'discounted_price');
            $source->addChild('totalOrderValueColumn')->addAttribute('value', 'total_order_value');
            $source->addChild('shippingCostsColumn')->addAttribute('value', 'shipping_costs');
            $source->addChild('orderReceptionDateColumn')->addAttribute('value', 'order_date');
            $source->addChild('orderConfirmationDateColumn')->addAttribute('value', 'confirmation_date');
            $source->addChild('orderShippingDateColumn')->addAttribute('value', 'shipping_date');
            $source->addChild('orderStatusColumn')->addAttribute('value', 'status');

            $this->sxml_append_options($source);
            //#########################################################################
        }

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $saveXML = $dom->saveXML();
        file_put_contents($name, $saveXML);
	}

    /**
     * @return array
     */
    protected function prepareProperties($attributes, $attributesValuesByName, $withTag)
    {

        $properties = array();

        $attrs = $attributes;

        if ($this->config->exportProductUrl($this->account)) {
            $attrs[] = 'default_url';
        }
		
		$doneId = false;

        foreach ($attrs as $attr) {
            // set property type
            switch ($attr) {
                case 'category_ids':
                    continue 2;
                case 'name':
                    $ptype = 'title';
                    break;
                case 'description':
                    $ptype = 'body';
                    break;
                case 'price':
                    $ptype = 'price';
                    break;
                case 'special_price':
                    $ptype = 'discounted';
                    break;
                case 'entity_id':
                    $ptype = 'id';
					$doneId = true;
                    break;
                case 'short_description':
                case 'status':
                case 'visibility':
                case 'default_url':
                    $ptype = 'text';
                    break;
                case 'weight':
                case 'width':
                case 'height':
                case 'length':
                    $ptype = 'number';
                    break;
                default:
                    $ptype = 'string';
            }

            if (isset($attributesValuesByName[$attr]) && $attr != 'visibility' && $attr != 'status') {
                $properties[] = array(
                    'id' => $attr,
                    'name' => $attr,
                    'ptype' => 'text',
                    'type' => 'reference',
                    'field' => $attr . '_id',
                    'has_lang' => false,
                    'reference' => $attr
                );
            } else {
                $ref = null;
                $type = 'direct';
                $field = $attr;
                switch ($attr) {
                    case 'description':
                    case 'short_description':
                    case 'visibility':
                    case 'status':
                    case 'name':
                    case 'default_url':
                        $lang = true;
                        break;
                    default:
                        $lang = false;
                }
                $properties[] = array(
                    'id' => $attr,
                    'name' => null,
                    'ptype' => $ptype,
                    'type' => $type,
                    'field' => $field,
                    'has_lang' => $lang,
                    'reference' => $ref
                );
            }
        }
        //tag
        if ($withTag) { //$this->_storeConfig['export_tags'] && 
            $properties[] = array(
                'id' => 'tag',
                'name' => 'tag',
                'ptype' => 'text',
                'type' => 'reference',
                'field' => 'tag_id',
                'has_lang' => false,
                'reference' => 'tag'
            );
        }

        //categories
        if (true) { //$this->_storeConfig['export_categories']
            $properties[] = array(
                'id' => 'category',
                'name' => 'categories', //property id
                'ptype' => 'hierarchical', //property type
                'type' => 'reference', //logic type
                'field' => 'category_id', //field colummn
                'has_lang' => false,
                'reference' => 'categories'
            );
        }

        //images
        if ($this->config->exportProductImages($this->account)) {
            $properties[] = array(
                'id' => 'cache_image_url',
                'name' => 'cache_image_url', //property id
                'ptype' => 'string', //property type
                'type' => 'direct', //logic type
                'field' => 'cache_image_url', //field colummn
                'has_lang' => false,
            );
			
            $properties[] = array(
                'id' => 'cache_image_thumbnail_url',
                'name' => 'cache_image_thumbnail_url', //property id
                'ptype' => 'string', //property type
                'type' => 'direct', //logic type
                'field' => 'cache_image_thumbnail_url', //field colummn
                'has_lang' => false,
            );
        }


        $properties[] = array(
            'id' => 'product_entity_id',
            'name' => null,
            'ptype' => 'string',
            'type' => 'direct',
            'field' => 'entity_id',
            'has_lang' => false,
            'reference' => null
        );
		
		if(!$doneId) {
			$properties[] = array(
				'id' => 'product_id',
				'name' => null,
				'ptype' => 'id',
				'type' => 'direct',
				'field' => 'entity_id',
				'has_lang' => false,
				'reference' => null
			);
		}

        return $properties;
    }

    /**
     * @desciption add default xmlElements
     * @param SimpleXMLElement $xml
     */
    protected function sxml_append_options(\SimpleXMLElement &$xml)
    {
        $xml->addChild('format')->addAttribute('value', $this->files->XML_FORMAT);
        $xml->addChild('encoding')->addAttribute('value', $this->files->XML_ENCODE);
        $xml->addChild('delimiter')->addAttribute('value', $this->files->XML_DELIMITER);
        $xml->addChild('enclosure')->addAttribute('value', $this->files->XML_ENCLOSURE);
        $xml->addChild('escape')->addAttribute('value', $this->files->XML_ESCAPE);
        $xml->addChild('lineSeparator')->addAttribute('value', $this->files->XML_NEWLINE);
    }
}
