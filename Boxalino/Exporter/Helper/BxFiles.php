<?php

namespace Boxalino\Exporter\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class BxFiles
{
	const URL_XML = '/frontend/dbmind/en/dbmind/api/data/source/update';
    const URL_XML_DEV = '/frontend/dbmind/en/dbmind/api/data/source/update?dev=true';
    const URL_ZIP = '/frontend/dbmind/en/dbmind/api/data/push';
    const URL_ZIP_DEV = '/frontend/dbmind/en/dbmind/api/data/push?dev=true';
    public $exportServer = '';
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
	
	/**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;
	
	/**
	* @var \Psr\Log\LoggerInterface
	*/
	protected $logger;
	
	protected $bxGeneral;
	
	public function __construct($filesystem, $logger, $account) {
		$this->filesystem = $filesystem;
		$this->logger = $logger;
		$this->account = $account;
		
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
	public function prepareProductFiles($files, $exportProductImages = true, $exportProductImageThumbnail = true) {
		
        foreach ($files as $attr) {

            $key = $attr;

            if ($attr == 'categories') {
                $key = 'category';
            }

            if (!file_exists($this->_dir)) {
                mkdir($this->_dir);
            }

            $file = 'product_' . $attr . '.csv';

            //save
            if (!in_array($file, $this->_files)) {
                $this->_files[] = $file;
            }

            $fh = fopen($this->_dir . '/' . $file, 'a');
            fputcsv($fh, array('entity_id', $key . '_id'), $this->XML_DELIMITER, $this->XML_ENCLOSURE);

            $this->filesMtM[$attr] = $fh;

        }

        if ($exportProductImages) {
            $file = 'product_cache_image_url.csv';
            if (!in_array($file, $this->_files)) {
                $this->_files[] = $file;
            }
            $fh = fopen($this->_dir . '/' . $file, 'a');
            $h = array('entity_id', 'cache_image_url');
            fputcsv($fh, $h, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
        }

        if ($exportProductImageThumbnail) {
            $file = 'product_cache_image_thumbnail_url.csv';
            if (!in_array($file, $this->_files)) {
                $this->_files[] = $file;
            }
            $fh = fopen($this->_dir . '/' . $file, 'a');
            $h = array('entity_id', 'cache_image_thumbnail_url');
            fputcsv($fh, $h, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
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

    /**
     * @description Preparing files to send
     */
    public function prepareGeneralFiles($account, $attributesValuesByName, &$categories = null, &$tags = null, $productTags = null)
    {

        //Prepare attributes
        $csvFiles = array();
        if (!file_exists($this->_dir)) {
            mkdir($this->_dir);
        }

        //create csvs
        //save attributes
        foreach ($attributesValuesByName as $attrName => $attrValues) {
            $csvFiles[] = $this->createCsv($this->bxGeneral->sanitizeFieldName($attrName), $attrValues);
        }

        //save categories
        if ($categories != null) {
            $csvFiles[] = $this->createCsv('categories', $categories);
            $categories = null;
        }

        //save tags
        if ($tags != null && $productTags != null) {
            $csvFiles[] = $this->createCsv('tag', $tags);

            $loop = 1;
            foreach ($productTags as $product_id => $tag_id) {
                $csvdata[] = array('id' => $loop++, 'entity_id' => $product_id, 'tag_id' => $tag_id);
            }

            $csvFiles[] = $this->createCsv('product_tag', $csvdata);
        }
        //csvs done

        //Create name for file
        $exportFile = $this->_dir . '/' . $account;
        $csvFiles = array_filter($csvFiles);

        return $exportFile;
    }

    /**
     * @param $name
     * @param $csvFiles
     */
    public function createZip($name, $xml)
    {
        if (file_exists($name)) {
            @unlink($name);
        };
		
		$csvFiles = array_filter($this->_files);

        $zip = new \ZipArchive();
        if ($zip->open($name, \ZipArchive::CREATE)) {

            foreach ($csvFiles as $f) {
                if (!$zip->addFile($this->_dir . '/' . $f, $f)) {
                    throw new Exception(
                        'Synchronization failure: Failed to add file "' .
                        $this->_dir . '/' . $f . '" to the zip "' .
                        $name . '". Please try again.'
                    );
                }
            }

            if (!$zip->addFile($xml, 'properties.xml')) {
                throw new Exception(
                    'Synchronization failure: Failed to add file "' .
                    $xml . '" to the zip "' .
                    $name . '". Please try again.'
                );
            }

            if (!$zip->close()) {
                throw new Exception(
                    'Synchronization failure: Failed to close the zip "' .
                    $name . '". Please try again.'
                );
            }

        } else {
            throw new Exception(
                'Synchronization failure: Failed to open the zip "' .
                $name . '" for writing. Please check the permissions and try again.'
            );
        }
    }
	
	public function addToCSV($file, $values) {
		fputcsv($this->filesMtM[$file], $values, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
	}
	
	public function closeFiles($files) {
		foreach ($files as $file) {
            fclose($this->filesMtM[$file]);
        }
	}

    /**
     * @return string URL to normal data sync
     * @param $dev
     */
    public function getZIPSyncUrl($exportServer, $dev = false)
    {
        if ($dev) {
            return $exportServer . self::URL_ZIP_DEV;
        } else {
            return $exportServer . self::URL_ZIP;
        }
    }

    /**
     * @return string URL to delta sync
     * @param $dev
     */
    public function getXMLSyncUrl($exportServer, $dev = false)
    {
        if ($dev) {
            return $exportServer . self::URL_XML_DEV;
        } else {
            return $exportServer . self::URL_XML;
        }

    }

    public function getError($responseBody)
    {
        $htmlTagsToReplace = array('body', 'p', 'br');
        $startPosition = strpos($responseBody, '<p>');
        $endPosition = strpos($responseBody, '&lt;br&gt;') + 3;
        $error = html_entity_decode(substr($responseBody, $startPosition, $endPosition));
        foreach ($htmlTagsToReplace as $tag) {
            $error = str_replace('<' . $tag . '>', PHP_EOL, $error);
        }
        return $error;
    }

    public function pushXML($file, $account, $username, $password, $exportServer, $isDev=false, $isDelta=false)
    {
        $fields = array(
            'username' => $username,
            'password' => $password,
            'account' => $account,
            'template' => 'standard_source',
            'xml' => file_get_contents($file . '.xml')
        );

        $url = $this->getXMLSyncUrl($exportServer, $isDev);
        return $this->pushFile($fields, $url, 'xml', $isDelta);

    }

    protected function pushFile($fields, $url, $type, $isDelta=false)
    {
        if ($isDelta && !in_array('products.csv', $this->_files)) {
            return 'skipped empty product delta sync';
        }

        $this->logger->info($type . ' push');
        $s = curl_init();

        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_TIMEOUT, 35000);
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_ENCODING, '');
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $fields);

        $responseBody = curl_exec($s);
        curl_close($s);
        if (strpos($responseBody, 'Internal Server Error') !== false) {
            $this->logger->info($type . ' push error: ' . $responseBody);
            Mage::throwException($this->getError($responseBody));;
        }
        $this->logger->info($type . ' pushed. Response: ' . $responseBody);
        return $responseBody;
    }

    /**
     * @param $zip
     */
    public function pushZip($file, $account, $username, $password, $exportServer, $isDev=false, $isDelta=false)
    {
        $fields = array(
            'username' => $username,
            'password' => $password,
            'account' => $account,
            'dev' => $isDev ? 'false' : 'true',
            'delta' => $isDelta,
            'data' => $this->getCurlFile("$file.zip", "application/zip"),
        );

        $url = $this->getZIPSyncUrl($exportServer, $isDev);

        return $this->pushFile($fields, $url, 'zip');
    }

    protected function getCurlFile($filename, $type)
    {
        try {
            if (class_exists('CURLFile')) {
                return new \CURLFile($filename, $type);
            }
        } catch(Exception $e) {}
        return "@$filename;type=$type";
    }
}
