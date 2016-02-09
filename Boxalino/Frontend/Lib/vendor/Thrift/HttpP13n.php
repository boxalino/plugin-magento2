<?php
namespace Boxalino\Frontend\Lib\vendor\Thrift;
class HttpP13n
{
    protected static $thriftClassLoader = null;
    protected $dependencies = array(
        '../../P13nService.php',
        '../../Types.php',
        '../../BxClient.php',
        '../../BxFacets.php',
        '../../BxFilter.php',
        '../../BxRecommendation.php',
        '../../BxSortFields.php'
    );
    protected $client = null;
    protected $transport = null;
    protected $host;
    protected $port = 443;
    protected $uri = '/p13n.web/p13n';
    protected $schema = 'https';
    protected $username;
    protected $password;

    /**
     *
     */
    public function __construct()
    {
        if (self::$thriftClassLoader === null) {
            $this->initClassLoader();
        }
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->getClient(), $name)) {
            if (isset($arguments[0])) {
                $arguments[0] = $this->addRequestContext($arguments[0]);
            }

            return call_user_func_array(array($this->getClient(), $name), $arguments);
        } else {
            throw new Exception("Method $name not supported in P13nService");
        }
    }

    /**
     * @param string $accountname
     * @param string $cookieDomain
     * @return string
     */
    public function getChoiceRequest($accountname, $cookieDomain = null)
    {
        $choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();

        // Setup information about account
        $userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
        $userRecord->username = $accountname;
        $choiceRequest->userRecord = $userRecord;

        // Setup request context
        $sessionid = $this->getSessionId();
        $profileid = $this->getVisitorId();

        $choiceRequest->profileId = $profileid;

        // Refresh cookies
        if(empty($cookieDomain)) {
            setcookie('cems', $sessionid, 0);
            setcookie('cemv', $profileid, time() + 1800);
        } else {
            setcookie('cems', $sessionid, 0, '/', $cookieDomain);
            setcookie('cemv', $profileid, time() + 1800, '/', $cookieDomain);
        }

        return $choiceRequest;
    }

    /**
     * @return \com\boxalino\p13n\api\thrift\P13nServiceClient
     */
    protected function getClient()
    {
        if ($this->client === null || $this->transport === null) {
            //if(function_exists('curl_version')) {
            //    $this->transport = new \Thrift\Transport\P13nTCurlClient($this->host, $this->port, $this->uri, $this->schema);
            //} else {
                $this->transport = new \Thrift\Transport\P13nTHttpClient($this->host, $this->port, $this->uri, $this->schema);
            //}
            $this->transport->setAuthorization($this->username, $this->password);
            $this->client = new \com\boxalino\p13n\api\thrift\P13nServiceClient(new \Thrift\Protocol\TCompactProtocol($this->transport));
        }
        return $this->client;
    }

    /**
     * @param string $username
     * @param string $password
     */
    public function setAuthorization($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        $this->transport = null;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
        $this->transport = null;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
        $this->transport = null;
    }

    /**
     * @param string $schema
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        $this->transport = null;
    }

    /**
     *
     */
    protected function initClassLoader()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'ClassLoader' . DIRECTORY_SEPARATOR . 'ThriftClassLoader.php';
        self::$thriftClassLoader = new \Thrift\ClassLoader\ThriftClassLoader(false);
        self::$thriftClassLoader->registerNamespace('Thrift', __DIR__ . DIRECTORY_SEPARATOR . '..');
        self::$thriftClassLoader->register();
        foreach ($this->dependencies as $dependency) {
            require_once __DIR__ . DIRECTORY_SEPARATOR . $dependency;
        }
    }

    /**
     * @return \com\boxalino\p13n\api\thrift\RequestContext
     */
    protected function getRequestContext()
    {
        $requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
        $requestContext->parameters = array(
            'User-Agent'     => array(@$_SERVER['HTTP_USER_AGENT']),
            'User-Host'      => array($this->getIP()),
            'User-SessionId' => array($this->getSessionId()),
            'User-Referer'   => array(@$_SERVER['HTTP_REFERER']),
            'User-URL'       => array($this->getCurrentURL())
        );

        if (isset($_REQUEST['p13nRequestContext']) && is_array($_REQUEST['p13nRequestContext'])) {
            $requestContext->parameters = array_merge(
                $_REQUEST['p13nRequestContext'],
                $requestContext->parameters
            );
        }

        return $requestContext;
    }

    /**
     * @return string
     */
    protected function getIP()
    {
        $ip = null;
        $clientip = @$_SERVER['HTTP_CLIENT_IP'];
        $forwardedip = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        if (filter_var($clientip, FILTER_VALIDATE_IP)) {
            $ip = $clientip;
        } elseif (filter_var($forwardedip, FILTER_VALIDATE_IP)) {
            $ip = $forwardedip;
        } else {
            $ip = @$_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * @return string
     */
    protected function getSessionId()
    {
        $sessionid = null;
        if (empty($_COOKIE['cems'])) {
            $sessionid = session_id();
            if (empty($sessionid)) {
                session_start();
                $sessionid = session_id();
            }
        } else {
            $sessionid = $_COOKIE['cems'];
        }

        return $sessionid;
    }

    /**
     * @return string
     */
    protected function getVisitorId()
    {
        $profileid = null;
        if (empty($_COOKIE['cemv'])) {
            $profileid = '';
            if (function_exists('openssl_random_pseudo_bytes')) {
                $profileid = bin2hex(openssl_random_pseudo_bytes(16));
            }
            if (empty($profileid)) {
                $profileid = uniqid('', true);
            }
        } else {
            $profileid = $_COOKIE['cemv'];
        }

        return $profileid;
    }

    /**
     * @return string
     */
    protected function getCurrentURL()
    {
        $protocol = strpos(strtolower(@$_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
        $hostname = @$_SERVER['HTTP_HOST'];
        $requesturi = @$_SERVER['REQUEST_URI'];

        return $protocol . '://' . $hostname . $requesturi;
    }

    /**
     * @param \com\boxalino\p13n\api\thrift\AutocompleteRequest|\com\boxalino\p13n\api\thrift\ChoiceRequest|\com\boxalino\p13n\api\thrift\BatchChoiceRequest $request
     * @return \com\boxalino\p13n\api\thrift\AutocompleteRequest|\com\boxalino\p13n\api\thrift\ChoiceRequest|\com\boxalino\p13n\api\thrift\BatchChoiceRequest
     */
    protected function addRequestContext($request)
    {
        if (
            $request instanceof \com\boxalino\p13n\api\thrift\AutocompleteRequest ||
            $request instanceof \com\boxalino\p13n\api\thrift\ChoiceRequest ||
            $request instanceof \com\boxalino\p13n\api\thrift\BatchChoiceRequest
        ) {
            if ($request->requestContext === null) {
                $request->requestContext = $this->getRequestContext();
            } else {
                $requestContext = $this->getRequestContext();
                foreach ($requestContext->parameters as $parameterName => $parameterValues) {
                    if (isset($request->requestContext->parameters[$parameterName])) {
                        continue;
                    }

                    $request->requestContext->parameters[$parameterName] = $parameterValues;
                }
            }
        }

        return $request;
    }

}
