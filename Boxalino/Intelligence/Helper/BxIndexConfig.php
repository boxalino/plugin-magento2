<?php

namespace Boxalino\Intelligence\Helper;
/**
 * Class BxIndexConfig
 * @package Boxalino\Intelligence\Helper
 */
class BxIndexConfig{
	
	/**
	 * @var array
	 */
	private $indexConfig = array();

	/**
	 * BxIndexConfig constructor.
	 * @param $websites
	 */
	public function __construct($websites) {
		
		$this->initialize($websites);
	}

	/**
	 * @param $websites
	 * @throws \Exception
	 */
	public function initialize($websites) {
		
		$this->indexConfig = array();
		foreach($websites  as $website) {
			
			foreach ($website->getGroups(true) as $group) {
				
				foreach ($group->getStores() as $store) {
					
					$enabled = $store->getConfig('bxExporter/exporter/enabled');
					if($enabled == '1') {

						$account = $store->getConfig('bxGeneral/general/account_name');
						
						if($account == "") {
							throw new \Exception(
								"Configuration error detected: Boxalino Account Name cannot be null for any store where exporter is enabled."
							);
						}
						
						$language = $store->getConfig('bxGeneral/advanced/language');
						
						if($language == "") {
							$locale = $store->getConfig('general/locale/code');
							$parts = explode('_', $locale);
							$language = $parts[0];
						}
						
						if (!array_key_exists($account, $this->indexConfig)) {
							$this->indexConfig[$account] = array();
						}

						if (array_key_exists($language, $this->indexConfig[$account])) {
							throw new \Exception(
								"Configuration error detected: Language '$language' can only be pushed to account '$account' once. Please review and correct your boxalino plugin's configuration, including the various configuration levels per website, store view, etc."
							);
						}
						
						$this->indexConfig[$account][$language] = array(
							'website' => $website,
							'group'   => $group,
							'store'   => $store,
						);
					}
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function getAccounts() {
		
		return array_keys($this->indexConfig);
	}

	/**
	 * @param $account
	 * @return array
	 * @throws \Exception
	 */
	public function getAccountLanguages($account) {
		
		return array_keys($this->getAccountArray($account));
	}

	/**
	 * @param $account
	 * @param $language
	 * @return mixed
	 * @throws \Exception
	 */
	public function getStore($account, $language) {
		
		$array = $this->getAccountLanguageArray($account, $language);
		return $array['store'];
	}

	/**
	 * @param $account
	 * @return mixed
	 * @throws \Exception
	 */
	private function getAccountArray($account) {
		
		if(isset($this->indexConfig[$account])) {
			return $this->indexConfig[$account];
		}
		throw new \Exception("Account is not defined: " . $account);
	}

	/**
	 * @param $account
	 * @return mixed
	 * @throws \Exception
	 */
	private function getAccountFirstLanguageArray($account) {
		
		$accountArray = $this->getAccountArray($account);
		foreach($accountArray as $l => $vals) {
			return $vals;
		}
		throw new \Exception("Account " . $account . " does not contain any language");
	}

	/**
	 * @param $account
	 * @param $language
	 * @return mixed
	 * @throws \Exception
	 */
	private function getAccountLanguageArray($account, $language) {
		
		$accountArray = $this->getAccountArray($account);
		if(isset($accountArray[$language])) {
			return $accountArray[$language];
		}
		throw new \Exception("Account " . $account . " does not contain a language " . $language);
	}

	/**
	 * @param $account
	 * @return mixed
	 * @throws \Exception
	 */
	public function getFirstAccountStore($account) {
		
		$array = $this->getAccountFirstLanguageArray($account);
		return $array['store'];
	}

	/**
	 * @param $account
	 * @return bool
	 */
	public function isCustomersExportEnabled($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/customers/enabled') == 1;
	}

	/**
	 * @param $account
	 * @return bool
	 */
	public function isTransactionsExportEnabled($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/transactions/enabled') == 1;
	}

	/**
	 * @param $account
	 * @return int
	 */
	public function getTransactionMode($account) {

		return (int) $this->getFirstAccountStore($account)->getConfig('bxExporter/transactions/export_mode');
	}

	/**
	 * @return string
	 */
	public function toString() {
		
		$lines = array();
		foreach($this->indexConfig as $a => $vs) {
			$lines[] = $a . " - " . implode(',', array_keys($vs));
		}
		return implode('\n', $lines);
	}

	/**
	 * @param $account
	 * @return mixed
	 */
	public function getAccountUsername($account) {
		
		$username = $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/username');
		return $username != "" ? $username : $account;
	}

	/**
	 * @param $account
	 * @return mixed
	 * @throws \Exception
	 */
	public function getAccountPassword($account) {
		
		$password = $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/password');
		if($password == '') {
			throw new \Exception("you must defined a password in Boxalino -> General configuration section");
		}
		return $password;
	}

	/**
	 * @param $account
	 * @return mixed
	 */
	public function isAccountDev($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/dev');
	}

	/**
	 * @param $account
	 * @return string
	 */
	public function getAccountExportServer($account) {
		
		$exportServer = $this->getFirstAccountStore($account)->getConfig('bxExporter/exporter/export_server');
		return $exportServer == '' ? 'http://di1.bx-cloud.com' : $exportServer;
	}

	/**
	 * @param $account
	 * @return bool
	 */
	public function exportProductImages($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/products/export_images') == 1;
	}

	/**
	 * @param $account
	 * @return bool
	 */
	public function exportProductUrl($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/products/export_url') == 1;
	}

	/**
	 * @param $account
	 * @return bool
	 */
	public function publishConfigurationChanges($account) {
		
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/advanced/publish_configuration_changes') == 1;
	}

	/**
	 * @param $allProperties
	 * @param $includes
	 * @param $excludes
	 * @param array $requiredProperties
	 * @return array
	 * @throws \Exception
	 */
	protected function getFinalProperties($allProperties, $includes, $excludes, $requiredProperties=array()) {
		
		foreach($includes as $k => $incl) {
			if($incl == "") {
				unset($includes[$k]);
			}
		}
		
		foreach($excludes as $k => $excl) {
			if($excl == "") {
				unset($excludes[$k]);
			}
		}
		
		if(sizeof($includes) > 0) {
			foreach($includes as $incl) {
				if(!in_array($incl, $allProperties)) {
					throw new \Exception("requested include property $incl which is not part of all the properties provided");
				}
				
				if(!in_array($incl, $requiredProperties)) {
					$requiredProperties[] = $incl;
				}
			}
			return $requiredProperties;
		}
		
		foreach($excludes as $excl) {
			if(!in_array($excl, $allProperties)) {
				throw new \Exception("requested exclude property $excl which is not part of all the properties provided");
			}
			if(in_array($excl, $requiredProperties)) {
				throw new \Exception("requested exclude property $excl which is part of the required properties and therefore cannot be excluded");
			}
		}
		
		$finalProperties = array();
		foreach($allProperties as $i => $p) {
			if(!in_array($p, $excludes)) {
				$finalProperties[$i] = $p;
			}
		}
		return $finalProperties;
	}

	/**
	 * @param $account
	 * @param $allProperties
	 * @param array $requiredProperties
	 * @return array
	 * @throws \Exception
	 */
	public function getAccountProductsProperties($account, $allProperties, $requiredProperties=array()) {
		
		$includes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/products/include_properties'));
		$excludes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/products/exclude_properties'));
		return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
	}

	/**
	 * @param $account
	 * @param $allProperties
	 * @param array $requiredProperties
	 * @return array
	 * @throws \Exception
	 */
	public function getAccountCustomersProperties($account, $allProperties, $requiredProperties=array()) {
		
		$includes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/customers/include_properties'));
		$excludes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/customers/exclude_properties'));
		return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
	}

	/**
	 * @param $account
	 * @param $allProperties
	 * @param array $requiredProperties
	 * @return array
	 * @throws \Exception
	 */
	public function getAccountTransactionsProperties($account, $allProperties, $requiredProperties=array()) {
		
		$includes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/transactions/include_properties'));
		$excludes = explode(',', $this->getFirstAccountStore($account)->getConfig('bxExporter/transactions/exclude_properties'));
		return $this->getFinalProperties($allProperties, $includes, $excludes, $requiredProperties);
	}
}
