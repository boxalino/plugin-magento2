<?php

namespace Boxalino\Intelligence\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Config\Definition\Exception\Exception;

class BxFiles
{
	public $XML_DELIMITER = ',';
    public $XML_ENCLOSURE = '"';
    public $XML_ENCLOSURE_TEXT = "&quot;"; // it's $XML_ENCLOSURE
    public $XML_NEWLINE = '\n';
    public $XML_ESCAPE = '\\\\';
    public $XML_ENCODE = 'UTF-8';
    public $XML_FORMAT = 'CSV';
    protected $_attributesWithIds = array();
    protected $_allTags = array();
    protected $_countries = array();
    protected $_languages = array(
        'en',
        'fr',
        'de',
        'it',
        'es',
        'zh',
        'cz',
        'ru',
    );
	
	protected $_mainDir = null;
	protected $_dir = null;
	
	private $account;
	private $config;
	
	/**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
	
	/**
	* @var \Psr\Log\LoggerInterface
	*/
	protected $logger;
	
	protected $bxGeneral;

	public function __construct($filesystem, $logger, $account, $config) {
		$this->filesystem = $filesystem;
		$this->logger = $logger;
		$this->account = $account;
		$this->config = $config;
		
		$this->bxGeneral = new BxGeneral();
		
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

	public function getPath($file) {
		if (!file_exists($this->_dir)) {
            mkdir($this->_dir);
        }

        //save
        if (!in_array($file, $this->_files)) {
            $this->_files[] = $file;
        }
		
		return $this->_dir . '/' . $file;
	}

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
	
	protected $_files = array();
	private $filesMtM = array();
	public function prepareProductFiles($files) {
		
        foreach ($files as $attrs) {
            foreach($attrs as $attr){
                $key = $attr['attribute_code'];


                if ($attr['attribute_code'] == 'categories') {
                    $key = 'category';
                }

                if (!file_exists($this->_dir)) {
                    mkdir($this->_dir);
                }

                $file = 'product_' . $attr['attribute_code'] . '.csv';

                //save
                if (!in_array($file, $this->_files)) {
                    $this->_files[] = $file;
                }

                $fh = fopen($this->_dir . '/' . $file, 'a');
//                fputcsv($fh, array('entity_id', $key . '_id', 'value'), $this->XML_DELIMITER, $this->XML_ENCLOSURE);

                $this->filesMtM[$attr['attribute_code']] = $fh;
            }
        }
	}

    /**
     * @param $name
     * @param $data
     * @return string
     */
    protected function createCsv($name, &$data)
    {
        $file = $name . '.csv';

        if (!is_array($data) || count($data) == 0) {
            $this->logger->warn("Data for $file is not an array or is empty. [" . gettype($data) . ']');
        }

        $csvdata = array_merge(array(array_keys(end($data))), $data);
        $csvdata[0][0] = $this->bxGeneral->sanitizeFieldName($csvdata[0][0]);

        $fh = fopen($this->_dir . '/' . $file, 'a');
        foreach ($csvdata as $dataRow) {
            fputcsv($fh, $dataRow, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
        }
        fclose($fh);

        $this->_files[] = $file;

        return $file;
    }
	
	public function addToCSV($file, $values) {
		fputcsv($this->filesMtM[$file], $values, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
	}
	
	public function closeFiles($files) {
		foreach ($files as $file) {
            fclose($this->filesMtM[$file]);
        }
	}
}
