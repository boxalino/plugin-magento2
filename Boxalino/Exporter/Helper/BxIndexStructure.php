<?php

namespace Boxalino\Exporter\Helper;

class BxIndexStructure
{
	private $indexStructure = array();
	
	public function __construct($websites) {
		$this->initialize($websites);
	}
	
	public function initialize($websites) {
		$this->indexStructure = array();
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
						
						if (!array_key_exists($account, $this->indexStructure)) {
							$this->indexStructure[$account] = array();
						}

						if (array_key_exists($language, $this->indexStructure[$account])) {
							throw new \Exception(
								"Configuration error detected: Language '$language' can only be pushed to account '$account' once. Please review and correct your boxalino plugin's configuration, including the various configuration levels per website, store view, etc."
							);
						}
						$this->indexStructure[$account][$language] = array(
							'website' => $website,
							'group'   => $group,
							'store'   => $store,
						);
					}
				}
			}
		}
	}
	
	public function getAccounts() {
		return array_keys($this->indexStructure);
	}
	
	public function getAccountLanguages($account) {
		return array_keys($this->getAccountArray($account));
	}
	
	public function getStore($account, $language) {
		$array = $this->getAccountLanguageArray($account, $language);
		return $array['store'];
	}
	
	private function getAccountArray($account) {
		if(isset($this->indexStructure[$account])) {
			return $this->indexStructure[$account];
		}
		throw new \Exception("Account is not defined: " . $account);
	}
	
	private function getAccountFirstLanguageArray($account) {
		$accountArray = $this->getAccountArray($account);
		foreach($accountArray as $l => $vals) {
			return $vals;
		}
		throw new \Exception("Account " . $account . " does not contain any language");
	}
	
	private function getAccountLanguageArray($account, $language) {
		$accountArray = $this->getAccountArray($account);
		if(isset($accountArray[$language])) {
			return $accountArray[$language];
		}
		throw new \Exception("Account " . $account . " does not contain a language " . $language);
	}
	
	public function getFirstAccountStore($account) {
		$array = $this->getAccountFirstLanguageArray($account);
		return $array['store'];
	}
	
	public function isCustomersExportEnabled($account) {
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/customers/enabled') == 1;
	}
	
	public function isTransactionsExportEnabled($account) {
		return $this->getFirstAccountStore($account)->getConfig('bxExporter/transactions/enabled') == 1;
	}
	
	public function toString() {
		$lines = array();
		foreach($this->indexStructure as $a => $vs) {
			$lines[] = $a . " - " . implode(',', array_keys($vs));
		}
		return implode('\n', $lines);
	}
	
	public function getAccountUsername($account) {
		$username = $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/username');
		return $username != "" ? $username : $account;
	}
	
	public function getAccountPassword($account) {
		$password = $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/password');
		if($password == '') {
			throw new \Exception("you must defined a password in Boxalino -> General configuration section");
		}
		return $password;
	}
	
	public function isAccountDev($account) {
		return $this->getFirstAccountStore($account)->getConfig('bxGeneral/general/dev') != 1;
	}
	
	public function getAccountExportServer($account) {
		$exportServer = $this->getFirstAccountStore($account)->getConfig('bxExporter/exporter/export_server');
		return $exportServer == '' ? 'http://di1.bx-cloud.com' : $exportServer;
	}
}
