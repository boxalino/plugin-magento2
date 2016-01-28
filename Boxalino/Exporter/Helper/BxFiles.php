<?php

namespace Boxalino\Exporter\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class BxFiles
{
	public $XML_DELIMITER = ',';
    public $XML_ENCLOSURE = '"';
    public $XML_ENCLOSURE_TEXT = "&quot;"; // it's $XML_ENCLOSURE
    public $XML_NEWLINE = '\n';
    public $XML_ESCAPE = '\\\\';
    public $XML_ENCODE = 'UTF-8';
    public $XML_FORMAT = 'CSV';
	
	protected $_mainDir = null;
	protected $_dir = null;
	
	private $account;
	
	/**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
	
	public function __construct($filesystem, $account) {
		$this->filesystem = $filesystem;
		$this->account = $account;
		$this->init();
	}
	
	public function init() {
		
		/** @var \Magento\Framework\Filesystem\Directory\Write $directory */
        $directory = $this->filesystem->getDirectoryWrite(
            DirectoryList::TMP
        );
		$directory->create();
		
		$this->_mainDir = $directory->getAbsolutePath() . "boxalino";
		if (!file_exists($this->_mainDir)) {
            mkdir($this->_mainDir);
        }
		
		$this->_dir = $this->_mainDir . '/' . $this->account;
		if (file_exists($this->_dir)) {
			$this->delTree($this->_dir);
		}
	}

    public function delTree($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delTree("$dir/$file");
            } else if (file_exists("$dir/$file")) {
                @unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }
	
	protected function getPath($file) {
		if (!file_exists($this->_dir)) {
            mkdir($this->_dir);
        }

        //save
        if (!in_array($file, $this->_files)) {
            $this->_files[] = $file;
        }
		
		return $this->_dir . '/' . $file;
	}

	protected $_files = array();
    public function savePartToCsv($file, &$data)
    {
		$path = $this->getPath($file);
		$fh = fopen($path, 'a');
        foreach ($data as $dataRow) {
            fputcsv($fh, $dataRow, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
        }
        fclose($fh);
        $data = null;
        $fh = null;

    }
	
	public function printFile($file) {
		$path = $this->getPath($file);
		echo file_get_contents($path);
	}
}
