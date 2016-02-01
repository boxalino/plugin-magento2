<?php
namespace Boxalino\Frontend\Model;
class Boxalino_Frontend_Model_Logger
{
    static private $_lastMemory = 0;
    static private $_startLogging = null;
    static private $_fileName = null;

    /**
     * @param string $type Error, Warning, Info, Success
     * @param array $data array('memory_usage' => '', 'method' => '', 'description' => ''),
     */
    static public function saveMemoryTracking($type, $loggedAction, array $data)
    {
        if (Mage::getStoreConfig('Boxalino_General/general/logs_saving')) {
            if (self::$_startLogging == null) {
                self::$_startLogging = date('d-m-Y_H:i:s');
            }
            if (self::$_fileName == null) {
                self::initFile($loggedAction . '-' . self::$_startLogging . '.txt');
            }
            $difference = $data['memory_usage'] - self::$_lastMemory;
            $line = date('d-m-Y H:i:s') . ' ' . strtoupper($type) . ': ' . $loggedAction . ' / ' . $data['method'] . ' / ' . $data['description'] . '. Memory usage: ' . $data['memory_usage'] . ' - Difference=' . $difference . "\n";
            file_put_contents(self::$_fileName, $line, FILE_APPEND);
            self::$_lastMemory = $data['memory_usage'];
        }
    }

    static public function saveFrontActions($type, $data, $separator = false)
    {

        if (!Mage::getStoreConfig('Boxalino_General/general/logs_saving_frontend')) {
            return;
        }

        $date = date('Y-m-d H:i:s');

        if (isset($_REQUEST['dev_bx_disp']) && $_REQUEST['dev_bx_disp'] == 'true') {
            print_r('<pre>');
            print_r($date . ' ' . strtoupper($type) . '<br/>');
            print_r($data);
            print_r('</pre>');

            print_r($separator ? "<br/>========================================================<br/>" : "<br/>");
        }

        $day = date('Y-m-d_H:i');


        // Create file if not exist
        $logDir = Mage::getBaseDir('var') . DS . 'boxalino_logs';
        $file = $logDir . DS . 'request_' . $day;

        if (!is_dir($logDir)) {
            mkdir($logDir);
            chmod($logDir, 0777);
        }

        if (!file_exists($file)) {
            file_put_contents($file, '');
            chmod($file, 0777);
        }

        //Save information into file
        $line = $date . ' ' . strtoupper($type) . "\n" . print_r($data, true) . ($separator ? "\n=========================================================================\n" : "\n");
        file_put_contents($file, $line, FILE_APPEND);
    }

    static private function initFile($name)
    {
        $logDir = Mage::getBaseDir('var') . DS . 'boxalino_logs';
        self::$_fileName = $logDir . DS . $name;

        if (!is_dir($logDir)) {
            mkdir($logDir);
            chmod($logDir, 0777);
        }

        if (!file_exists(self::$_fileName)) {
            file_put_contents(self::$_fileName, '');
            chmod(self::$_fileName, 0777);
        }
    }
}
