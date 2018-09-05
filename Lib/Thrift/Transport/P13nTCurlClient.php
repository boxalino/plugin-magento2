<?php
namespace Thrift\Transport;

use Thrift\Transport\TTransport;
use Thrift\Exception\TTransportException;
use Thrift\Factory\TStringFuncFactory;

class P13nTCurlClient extends TCurlClient
{

    /**
     * @var
     */
    private static $curlHandle;

    /**
     * @var string
     */
    protected $authorizationString;

    /**
     * @var int
     */
    protected $timeout = 1000;

    /**
     * @var string
     */
    protected $profileId = 0;


    /**
     * Opens and sends the actual request over the HTTP connection
     *
     * @throws TTransportException if a writing error occurs
     */
    public function flush() {
        if (!self::$curlHandle) {
            //register_shutdown_function(array('Thrift\\Transport\\TCurlClient', 'closeCurlHandle'));
            self::$curlHandle = curl_init();
            curl_setopt(self::$curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$curlHandle, CURLOPT_BINARYTRANSFER, true);
            curl_setopt(self::$curlHandle, CURLOPT_USERAGENT, 'PHP/TCurlClient');
            curl_setopt(self::$curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt(self::$curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            // FOLLOWLOCATION cannot be activated when safe_mode is enabled or an open_basedir is set
            @curl_setopt(self::$curlHandle, CURLOPT_FOLLOWLOCATION, true);
            @curl_setopt(self::$curlHandle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt(self::$curlHandle, CURLOPT_CONNECTTIMEOUT_MS, $this->curl_timeout);
            curl_setopt(self::$curlHandle, CURLOPT_MAXREDIRS, 1);
        }
        $host = $this->host_.($this->port_ != 80 ? ':'.$this->port_ : '');
        $fullUrl = $this->scheme_."://".$host.$this->uri_;

        $headers = array('Host: '.$host,
            'Accept: application/x-thrift',
            'User-Agent: PHP/THttpClient',
            'Content-Type: application/x-thrift',
            'X-BX-PROFILEID: '. $this->profileId,
            'Content-Length: '.TStringFuncFactory::create()->strlen($this->request_),
            'Authorization: Basic '.$this->authorizationString);

        curl_setopt(self::$curlHandle, CURLOPT_HTTPHEADER, $headers);

        if ($this->timeout_ > 0) {
            curl_setopt(self::$curlHandle, CURLOPT_TIMEOUT, $this->timeout_);
        }
        curl_setopt(self::$curlHandle, CURLOPT_POSTFIELDS, $this->request_);
        $this->request_ = '';

        curl_setopt(self::$curlHandle, CURLOPT_URL, $fullUrl);
        $this->response_ = curl_exec(self::$curlHandle);

        // Connect failed?
        if (!$this->response_) {
            curl_close(self::$curlHandle);
            self::$curlHandle = null;
            $error = 'TCurlClient: Could not connect to '.$fullUrl;
            throw new TTransportException($error, TTransportException::NOT_OPEN);
        }
    }

    static function closeCurlHandle() {
        try {
            if (self::$curlHandle) {
                curl_close(self::$curlHandle);
                self::$curlHandle = null;
            }
        } catch (\Exception $x) {
            error_log('There was an error closing the curl handle: ' . $x->getMessage());
        }
    }

    /**
     * @param $username
     * @param $password
     * @return $this
     */
    public function setAuthorization($username, $password)
    {
        $this->authorizationString = base64_encode($username.':'.$password);
        return $this;
    }

    /**
     * adding tracker for the node-pinning architecture
     *
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;
        return $this;
    }

    /**
     * adding tracker for the node-pinning architecture
     *
     * @param $profileId
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }
}
