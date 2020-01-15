<?php
namespace com\boxalino\bxclient\v1;

class BxClient
{
    CONST BOXALINO_DEBUG_REQUEST = "boxalino_request";
    CONST BOXALINO_DEBUG_RESPONSE = "boxalino_response";
    CONST BOXALINO_TECH_DEBUG = "dev_bx_disp";
    CONST BOXALINO_TECH_SOCKET = "dev_bx_socket";
    CONST BOXALINO_TECH_CHOICE = "dev_bx_choice";
    CONST BOXALINO_TECH_TEST = "dev_bx_test_mode";
    CONST BOXALINO_TECH_NOTIFICATIONS = "dev_bx_notifications";
    CONST BOXALINO_TECH_BENCHMARK = "dev_bx_debug";

    protected $nondebugableChoices = ['autocomplete'];

    protected $debugContext = [
        self::BOXALINO_TECH_BENCHMARK,
        self::BOXALINO_DEBUG_REQUEST,
        self::BOXALINO_DEBUG_RESPONSE,
        self::BOXALINO_TECH_DEBUG
    ];

    private $account;
    private $password;
    private $isDev;
    private $host;
    private $apiKey;
    private $apiSecret;
    private $port;
    private $uri;
    private $schema;
    private $p13n_username;
    private $p13n_password;
    private $domain;

    private $isTest = null;

    private $debugOutput = '';
    private $debugOutputActive = false;
    private $autocompleteRequests = null;
    private $autocompleteResponses = null;

    private $chooseRequests = [];
    private $chooseResponses = null;

    private $bundleChooseRequests = [];

    const VISITOR_COOKIE_TIME = 31536000;
    const BXL_UUID_REQUEST = "_system_requestid";

    private $_timeout = 2;
    private $curl_timeout = 2000;
    private $requestContextParameters = [];

    private $sessionId = null;
    private $profileId = null;

    private $requestMap = [];
    protected $sendRequestId = false;
    protected $uuid = null;

    private $socketHost = null;
    private $socketPort = null;
    private $socketSendTimeout = null;
    private $socketRecvTimeout = null;

    private $notifications = [];

    public function __construct($account, $password, $domain, $isDev=false, $host=null, $port=null, $uri=null, $schema=null, $p13n_username=null, $p13n_password=null, $request=null, $apiKey=null, $apiSecret=null) {
        $this->account = $account;
        $this->password = $password;
        $this->requestMap = $request;
        if($this->requestMap == null) {
            $this->requestMap = $_REQUEST;
        }
        $this->isDev = $isDev;
        $this->apiKey = $apiKey;
        if (empty($apiKey)) {
            $this->apiKey = null;
        }
        $this->apiSecret = $apiSecret;
        if (empty($apiSecret)) {
            $this->apiSecret = null;
        }
        $this->host = $this->setHost($host);
        $this->port = $port;
        if($this->port == null) {
            $this->port = 443;
        }
        $this->uri = $uri;
        if($this->uri == null) {
            $this->uri = '/p13n.web/p13n';
        }
        $this->schema = $schema;
        if($this->schema == null) {
            $this->schema = 'https';
        }
        $this->p13n_username = $p13n_username;
        if($this->p13n_username == null) {
            $this->p13n_username = "boxalino";
        }
        $this->p13n_password = $p13n_password;
        if($this->p13n_password == null) {
            $this->p13n_password = "tkZ8EXfzeZc6SdXZntCU";
        }
        $this->domain = $domain;
    }

    /**
     * Setting the host based on configurations
     *
     * @param null $host
     * @return null|string
     */
    public function setHost($host=null)
    {
        if(!empty($host))
        {
            return $host;
        }

        if($this->apiSecret == null && $this->apiKey == null)
        {
            return "cdn.bx-cloud.com";
        }

        if($this->isDev)
        {
            return "r-st.bx-cloud.com";
        }

        return "main.bx-cloud.com";
    }

    public function setApiKey($apiKey){
        $this->apiKey = $apiKey;
    }

    public function setApiSecret($apiSecret) {
        $this->apiSecret = $apiSecret;
    }

    public function setTestMode($isTest) {
        $this->isTest = $isTest;
    }

    public function setSocket($socketHost, $socketPort=4040, $socketSendTimeout=1000, $socketRecvTimeout=1000) {
        $this->socketHost = $socketHost;
        $this->socketPort = $socketPort;
        $this->socketSendTimeout = $socketSendTimeout;
        $this->socketRecvTimeout = $socketRecvTimeout;
    }

    public function setRequestMap($requestMap) {
        $this->requestMap = $requestMap;
    }

    private $choiceIdOverwrite = "owbx_choice_id";
    public function getChoiceIdOverwrite()
    {
        if (isset($this->requestMap[$this->choiceIdOverwrite])) {
            return $this->requestMap[$this->choiceIdOverwrite];
        }
        return null;
    }

    public function getRequestMap() {
        return $this->requestMap;
    }

    public function addToRequestMap($key, $value) {
        $this->requestMap[$key] = $value;
    }

    public static function LOAD_CLASSES($libPath)
    {
        $cl = new \Thrift\ClassLoader\ThriftClassLoader(false);
        $cl->registerNamespace('Thrift', $libPath);
        $cl->register(true);
        $folders = ['P13n'];
        $deferred = '';
        foreach ($folders as $folder) {
            $files =  glob($libPath . '/'. $folder . '/*.php', GLOB_NOSORT);
            foreach ($files as $file) {
                if(strpos($file, 'P13nServiceClient') !== false){
                    $deferred = $file;
                    continue;
                }
                require_once($file);
            }
        }
        if($deferred !== ''){
            require_once($deferred);
        }
        require_once($libPath . "/BxFacets.php");
        require_once($libPath . "/BxFilter.php");
        require_once($libPath . "/BxRequest.php");
        require_once($libPath . "/BxRecommendationRequest.php");
        require_once($libPath . "/BxParametrizedRequest.php");
        require_once($libPath . "/BxSearchRequest.php");
        require_once($libPath . "/BxAutocompleteRequest.php");
        require_once($libPath . "/BxSortFields.php");
        require_once($libPath . "/BxChooseResponse.php");
        require_once($libPath . "/BxAutocompleteResponse.php");
        require_once($libPath . "/BxData.php");
    }

    public function getAccount($checkDev = true) {
        if($checkDev && $this->isDev) {
            return $this->account . '_dev';
        }
        return $this->account;
    }

    public function getUsername() {
        return $this->getAccount(false);
    }

    public function getPassword() {
        return $this->password;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function getApiSecret() {
        return $this->apiSecret;
    }

    public function setSessionAndProfile($sessionId, $profileId) {
        $this->sessionId = $sessionId;
        $this->profileId = $profileId;
    }

    public function getSessionAndProfile()
    {
        if($this->sessionId != null && $this->profileId != null) {
            return array($this->sessionId, $this->profileId);
        }

        if (empty($_COOKIE['cems'])) {
            $sessionId = session_id();
            if (empty($sessionId)) {
                @session_start();
                $sessionId = session_id();
            }
        } else {
            $sessionId = $_COOKIE['cems'];
        }

        if (empty($_COOKIE['cemv'])) {
            $profileId = session_id();
            if (empty($profileId)) {
                @session_start();
                $profileId = session_id();
            }
        } else {
            $profileId = $_COOKIE['cemv'];
        }

        // Refresh cookies
        if (empty($this->domain)) {
            @setcookie('cems', $sessionId, 0);
            @setcookie('cemv', $profileId, time() + self::VISITOR_COOKIE_TIME);
        } else {
            @setcookie('cems', $sessionId, 0, '/', $this->domain);
            @setcookie('cemv', $profileId, time() + self::VISITOR_COOKIE_TIME, '/', $this->domain);
        }

        $this->sessionId = $sessionId;
        $this->profileId = $profileId;

        return array($this->sessionId, $this->profileId);
    }

    private function getUserRecord() {
        $userRecord = new \com\boxalino\p13n\api\thrift\UserRecord();
        $userRecord->username = $this->getAccount();
        $userRecord->apiKey = $this->getApiKey();
        $userRecord->apiSecret = $this->getApiSecret();
        return $userRecord;
    }

    public function setCurlTimeout($timeout) {
        $this->curl_timeout = $timeout;
    }

    private function getP13n($timeout=2, $useCurlIfAvailable=true)
    {
        list($sessionId, $profileId) = $this->getSessionAndProfile();

        if (isset($this->requestMap[self::BOXALINO_TECH_SOCKET])) {
            $this->setSocket($this->requestMap[self::BOXALINO_TECH_SOCKET]);
        }

        if($this->socketHost != null) {
            $transport = new \Thrift\Transport\TSocket($this->socketHost, $this->socketPort);
            $transport->setSendTimeout($this->socketSendTimeout);
            $transport->setRecvTimeout($this->socketRecvTimeout);
            $client = new \com\boxalino\p13n\api\thrift\P13nServiceClient(new \Thrift\Protocol\TBinaryProtocol($transport));
            $transport->open();
            return $client;
        }

        if($useCurlIfAvailable && function_exists('curl_version')) {
            $transport = new \Thrift\Transport\P13nTCurlClient($this->host, $this->port, $this->uri, $this->schema);
            $transport->setTimeout($this->curl_timeout);
        } else {
            $transport = new \Thrift\Transport\P13nTHttpClient($this->host, $this->port, $this->uri, $this->schema);
        }

        $transport->setProfileId($profileId);
        $transport->setAuthorization($this->p13n_username, $this->p13n_password);
        $transport->setTimeoutSecs($timeout);
        $client = new \com\boxalino\p13n\api\thrift\P13nServiceClient(new \Thrift\Protocol\TCompactProtocol($transport));
        $transport->open();
        return $client;
    }

    public function getChoiceRequest($inquiries, $requestContext = null)
    {
        $choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();

        list($sessionid, $profileid) = $this->getSessionAndProfile();
        $choiceRequest->userRecord = $this->getUserRecord();
        $choiceRequest->profileId = $profileid;
        $choiceRequest->inquiries = $inquiries;
        if($requestContext == null) {
            $requestContext = $this->getRequestContext();
        }
        $choiceRequest->requestContext = $requestContext;

        return $choiceRequest;
    }

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

    protected function getCurrentURL()
    {
        $protocol = strpos(strtolower(@$_SERVER['SERVER_PROTOCOL']), 'https') === false ? 'http' : 'https';
        $hostname = @$_SERVER['HTTP_HOST'];
        $requesturi = @$_SERVER['REQUEST_URI'];

        if($hostname == "") {
            return "";
        }

        return $protocol . '://' . $hostname . $requesturi;
    }

    public function forwardRequestMapAsContextParameters($filterPrefix = '', $setPrefix = ''){
        foreach ($this->requestMap as $key => $value) {
            if($filterPrefix != ''){
                if(strpos($key, $filterPrefix) !== 0) {
                    continue;
                }
            }
            $this->requestContextParameters[$setPrefix . $key] = is_array($value) ? $value : array($value);
        }
    }

    public function addRequestContextParameter($name, $values) {
        if(!is_array($values)) {
            $values = array($values);
        }
        $this->requestContextParameters[$name] = $values;
    }

    public function resetRequestContextParameter() {
        $this->requestContextParameters = [];
    }

    protected function getBasicRequestContextParameters()
    {
        list($sessionid, $profileid) = $this->getSessionAndProfile();
        return array(
            'User-Agent'	 => array(@$_SERVER['HTTP_USER_AGENT']),
            'User-Host'	  => array($this->getIP()),
            'User-SessionId' => array($sessionid),
            'User-Referer'   => array(@$_SERVER['HTTP_REFERER']),
            'User-URL'	   => array($this->getCurrentURL())
        );
    }

    public function getRequestContextParameters() {
        $params = $this->requestContextParameters;
        foreach($this->chooseRequests as $request) {
            foreach($request->getRequestContextParameters() as $k => $v) {
                if(!is_array($v)) {
                    $v = array($v);
                }
                $params[$k] = $v;
            }
        }
        return $params;
    }

    protected function getRequestContext()
    {
        $requestContext = new \com\boxalino\p13n\api\thrift\RequestContext();
        $requestContext->parameters = $this->getBasicRequestContextParameters();

        if(function_exists("random_bytes") && $this->sendRequestId)
        {
            $this->addRequestContextParameter(self::BXL_UUID_REQUEST, $this->uuid());
        }

        foreach($this->getRequestContextParameters() as $k => $v)
        {
            $requestContext->parameters[$k] = $v;
        }

        if (isset($this->requestMap['p13nRequestContext']) && is_array($this->requestMap['p13nRequestContext']))
        {
            $requestContext->parameters = array_merge($this->requestMap['p13nRequestContext'],$requestContext->parameters);
        }

        return $requestContext;
    }

    private function throwCorrectP13nException($e) {
        if(strpos($e->getMessage(), 'Could not connect ') !== false) {
            throw new \Exception('The connection to our server failed before checking your credentials. This might be typically caused by 2 possible things: wrong values in host/schema/port (for exports), api key or api secret (your values are : host=' . $this->host . ', port=' . $this->port . ', schema=' . $this->schema . ', uri=' . $this->uri .  ', api key =' . $this->getApiKey() .', request: ' . $this->getRequestId() . '). Another possibility, is that your server environment has a problem with ssl certificate (peer certificate cannot be authenticated with given ca certificates) which can be fixed. Full error message=' . $e->getMessage());
        }

        if(strpos($e->getMessage(), 'Bad protocol id in TCompact message') !== false) {
            throw new \Exception('The connection to our server has worked, but your credentials were refused. Provided credentials username=' . $this->p13n_username. ', password=' . $this->p13n_password . ', account=' . $this->account . ', host=' . $this->host .  ', api key =' . $this->getApiKey() . ', request: ' . $this->getRequestId() . '. Full error message=' . $e->getMessage());
        }

        if(strpos($e->getMessage(), 'choice not found') !== false) {
            $parts = explode('choice not found', $e->getMessage());
            $pieces = explode('	at ', $parts[1]);
            $choiceId = str_replace(':', '', trim($pieces[0]));
            throw new \Exception("Configuration IS not live on account " . $this->getAccount() . ": choice/widget $choiceId doesn't exist. NB: If you get a message indicating that the choice doesn't exist, go to http://intelligence.bx-cloud.com, log in your account and make sure that the choice ID you want to use is published.");
        }
        if(strpos($e->getMessage(), 'Solr returned status 404') !== false) {
            throw new \Exception("Data is not live on account " . $this->getAccount() . ": index returns status 404. Please publish your data first, like in example backend_data_basic.php.");
        }

        if(strpos($e->getMessage(), 'undefined field') !== false) {
            $parts = explode('undefined field', $e->getMessage());
            $pieces = explode('	at ', $parts[1]);
            $field = str_replace(':', '', trim($pieces[0]));
            throw new \Exception("The request is done on a filter or facets of a non-existing field of your account " . $this->getAccount() . ": field $field doesn't exist. Request: " . $this->getRequestId());
        }

        if(strpos($e->getMessage(), 'All choice variants are excluded') !== false) {
            throw new \Exception("You have an invalid configuration for a choice defined. This is a quite unusual case, please contact support@boxalino.com to get support. Request: " . $this->getRequestId());
        }

        throw $e;
    }

    private function p13nchoose($choiceRequest) {
        try {
            $choiceResponse = $this->getP13n($this->_timeout)->choose($choiceRequest);
            if($this->debugContextAvailable()) {
                $debug = true;
                if (isset($this->requestMap[self::BOXALINO_TECH_CHOICE])) {
                    $debug = false;
                    foreach ($choiceRequest->inquiries as $inquiry) {
                        if ($inquiry->choiceId == $this->requestMap[self::BOXALINO_TECH_CHOICE]) {
                            $debug = true;
                            break;
                        }
                    }
                }
                if ($debug) {
                    $this->debug($choiceRequest, $choiceResponse, "p13nchoose", false);
                }
            }

            $this->debug($choiceRequest, $choiceResponse, "p13nchoose", true, false);
            return $choiceResponse;
        } catch(\Exception $e) {
            $this->throwCorrectP13nException($e);
        }
    }

    private function p13nchooseAll($choiceRequestBundle) {
        try {
            $bundleChoiceResponse = $this->getP13n($this->_timeout)->chooseAll($choiceRequestBundle);
            $this->debug($choiceRequestBundle, $bundleChoiceResponse, "p13nchooseAll");
            return $bundleChoiceResponse;
        } catch(\Exception $e) {
            $this->throwCorrectP13nException($e);
        }
    }

    public function addRequest($request) {
        $request->setDefaultIndexId($this->getAccount());
        $request->setDefaultRequestMap($this->requestMap);
        $this->chooseRequests[] = $request;
        return sizeof($this->chooseRequests)-1;
    }

    public function addBundleRequest($requests) {
        foreach ($requests as $request) {
            $request->setDefaultIndexId($this->getAccount());
            $request->setDefaultRequestMap($this->requestMap);
        }
        $this->bundleChooseRequests[] = $requests;
    }

    public function resetRequests() {
        $this->chooseRequests = [];
        $this->bundleChooseRequests = [];
    }

    public function getRequest($index=0) {
        if(sizeof($this->chooseRequests) <= $index) {
            return null;
        }
        return $this->chooseRequests[$index];
    }

    public function getChoiceIdRecommendationRequest($choiceId) {
        foreach ($this->chooseRequests as $request){
            if($request->getChoiceId() == $choiceId) {
                return $request;
            }
        }
        return null;
    }

    public function getRecommendationRequests(){
        $requests = [];
        foreach ($this->chooseRequests as $request){
            if($request instanceof BxRecommendationRequest){
                $requests[] = $request;
            }
        }
        return $requests;
    }

    public function getThriftChoiceRequest($size=0)
    {
        if(sizeof($this->chooseRequests) == 0 && sizeof($this->autocompleteRequests) > 0) {
            list($sessionid, $profileid) = $this->getSessionAndProfile();
            $userRecord = $this->getUserRecord();
            $p13nrequests = array_map(function($request) use(&$profileid, &$userRecord) {
                return $request->getAutocompleteThriftRequest($profileid, $userRecord);
            }, $this->autocompleteRequests);
            return $p13nrequests;
        }

        $choiceInquiries = [];
        $requests = $size === 0 ? $this->chooseRequests : array_slice($this->chooseRequests, -$size);
        foreach($requests as $request) {
            $choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
            $choiceInquiry->choiceId = $request->getChoiceId();
            if(sizeof($choiceInquiries) == 0 && $this->getChoiceIdOverwrite()) {
                $choiceInquiry->choiceId = $this->getChoiceIdOverwrite();
            }
            if($this->isTest === true || ($this->isDev && $this->isTest === null)) {
                $choiceInquiry->choiceId .= "_debugtest";
            }
            $choiceInquiry->simpleSearchQuery = $request->getSimpleSearchQuery($this->getAccount());
            $choiceInquiry->contextItems = $request->getContextItems();
            $choiceInquiry->minHitCount = $request->getMin();
            $choiceInquiry->withRelaxation = $request->getWithRelaxation();

            $choiceInquiries[] = $choiceInquiry;
        }

        $choiceRequest = $this->getChoiceRequest($choiceInquiries, $this->getRequestContext());
        return $choiceRequest;
    }

    public function getBundleChoiceRequest($inquiries, $requestContext = null)
    {
        $choiceRequest = new \com\boxalino\p13n\api\thrift\ChoiceRequest();
        list($sessionid, $profileid) = $this->getSessionAndProfile();

        $choiceRequest->userRecord = $this->getUserRecord();
        $choiceRequest->profileId = $profileid;
        $choiceRequest->inquiries = $inquiries;
        if($requestContext == null) {
            $requestContext = $this->getRequestContext();
        }
        $choiceRequest->requestContext = $requestContext;
        return $choiceRequest;
    }

    public function getThriftBundleChoiceRequest()
    {
        $bundleRequest = [];
        foreach($this->bundleChooseRequests as $bundleChooseRequest) {
            $choiceInquiries = [];
            foreach ($bundleChooseRequest as $request) {
                $this->addRequest($request);
                $choiceInquiry = new \com\boxalino\p13n\api\thrift\ChoiceInquiry();
                $choiceInquiry->choiceId = $request->getChoiceId();
                if($this->isTest === true || ($this->isDev && $this->isTest === null)) {
                    $choiceInquiry->choiceId .= "_debugtest";
                }
                $choiceInquiry->simpleSearchQuery = $request->getSimpleSearchQuery($this->getAccount());
                $choiceInquiry->contextItems = $request->getContextItems();
                $choiceInquiry->minHitCount = $request->getMin();
                $choiceInquiry->withRelaxation = $request->getWithRelaxation();
                $choiceInquiries[] = $choiceInquiry;
            }
            $bundleRequest[] = $this->getBundleChoiceRequest($choiceInquiries, $this->getRequestContext());
        }
        return new \com\boxalino\p13n\api\thrift\ChoiceRequestBundle(['requests' => $bundleRequest]);
    }

    protected function choose($chooseAll=false, $size=0) {
        if($chooseAll) {
            $bundleResponse = $this->p13nchooseAll($this->getThriftBundleChoiceRequest());
            $variants = [];
            foreach ($bundleResponse->responses as $choiceResponse) {
                $variants = array_merge($variants, $choiceResponse->variants);
            }

            $response = new \com\boxalino\p13n\api\thrift\ChoiceResponse(['variants' => $variants]);
        } else {
            $response = $this->p13nchoose($this->getThriftChoiceRequest($size));
            if($size > 0) {
                $response->variants = array_merge($this->chooseResponses->variants, $response->variants);
            }
        }
        $this->chooseResponses = $response ;
    }

    public function flushResponses() {
        $this->autocompleteResponses = null;
        $this->chooseResponses = null;
    }

    public function getResponse($chooseAll=false) {
        if(!$this->chooseResponses) {
            $this->choose($chooseAll);
        }elseif ($size = sizeof($this->chooseRequests) - sizeof($this->chooseResponses->variants)) {
            $this->choose($chooseAll, $size);
        }
        $bxChooseResponse = new \com\boxalino\bxclient\v1\BxChooseResponse($this->chooseResponses, $this->chooseRequests);
        $bxChooseResponse->setNotificationMode($this->getNotificationMode());
        return $bxChooseResponse;
    }

    public function getNotificationMode() {
        return isset($this->requestMap[self::BOXALINO_TECH_NOTIFICATIONS]) && $this->requestMap[self::BOXALINO_TECH_NOTIFICATIONS] == 'true';
    }

    public function setAutocompleteRequest($request) {
        $this->setAutocompleteRequests(array($request));
    }

    public function setAutocompleteRequests($requests) {
        foreach ($requests as $request) {
            $this->enhanceAutoCompleterequest($request);
        }
        $this->autocompleteRequests = $requests;
    }

    private function enhanceAutoCompleterequest(&$request) {
        $request->setDefaultIndexId($this->getAccount());
    }

    private function p13nautocomplete($autocompleteRequest) {
        try {
            $choiceResponse = $this->getP13n($this->_timeout)->autocomplete($autocompleteRequest);
            $this->debug($autocompleteRequest, $choiceResponse, "autocomplete");
            return $choiceResponse;
        } catch(\Exception $e) {
            $this->throwCorrectP13nException($e);
        }
    }

    public function autocomplete()
    {
        list($sessionid, $profileid) = $this->getSessionAndProfile();
        $userRecord = $this->getUserRecord();
        $p13nrequests = array_map(function($request) use(&$profileid, &$userRecord) {
            return $request->getAutocompleteThriftRequest($profileid, $userRecord);
        }, $this->autocompleteRequests);
        $i = -1;
        $this->autocompleteResponses = array_map(function($response) use (&$i) {
            $request = $this->autocompleteRequests[++$i];
            return new BxAutocompleteResponse($response, $request);
        }, $this->p13nautocompleteAll($p13nrequests));

    }

    public function getAutocompleteResponse() {
        $responses = $this->getAutocompleteResponses();
        if(isset($responses[0])) {
            return $responses[0];
        }
        return null;
    }

    private function p13nautocompleteAll($requests) {
        $requestBundle = new \com\boxalino\p13n\api\thrift\AutocompleteRequestBundle();
        $requestBundle->requests = $requests;
        try {
            $choiceResponse = $this->getP13n($this->_timeout)->autocompleteAll($requestBundle)->responses;
            $this->debug($requestBundle, $choiceResponse, "bundle");

            return $choiceResponse;
        } catch(\Exception $e) {
            $this->throwCorrectP13nException($e);
        }
    }

    public function getAutocompleteResponses() {
        if (!$this->autocompleteResponses) {
            $this->autocomplete();
        }
        return $this->autocompleteResponses;
    }

    public function sendAllChooseRequests($chooseAll = false)
    {
        if(!empty($this->chooseRequests))
        {
            $this->choose($chooseAll);
        }

        $this->flushResponses();
        $this->resetRequests();
        $this->resetRequestContextParameter();
    }

    public function setTimeout($timeout) {
        $this->_timeout = $timeout;
        return $this;
    }

    public function getDebugOutput(){
        return $this->debugOutput;
    }

    public function setDebugOutputActive($debugOutputActive) {
        $this->debugOutputActive = $debugOutputActive;
    }

    public function notifyWarning($warning) {
        $this->addNotification("warning", $warning);
    }

    public function addNotification($type, $notification) {
        if(!isset($this->notifications[$type])) {
            $this->notifications[$type] = [];
        }
        $this->notifications[$type][] = $notification;
    }

    public function getNotifications() {
        $final = $this->notifications;
        $final['response'] = $this->getResponse()->getNotifications();
        return $final;
    }

    public function finalNotificationCheck($force=false, $requestMapKey = self::BOXALINO_TECH_NOTIFICATIONS)
    {
        if ($force || (isset($this->requestMap[$requestMapKey]) && $this->requestMap[$requestMapKey] == 'true')) {
            $value = "<pre><h1>Notifications</h1>" .  var_export($this->notifications, true) . "</pre>";
            if(!$this->debugOutputActive) {
                echo $value;
                exit;
            }
            return $value;
        }
    }

    protected function debugContextAvailable()
    {
        $context = array_intersect(array_keys($this->requestMap), $this->debugContext);
        if(empty($context))
        {
            return false;
        }

        foreach($context as $param)
        {
            if($this->requestMap[$param] == 'true')
            {
                return true;
            }
        }

        return false;
    }

    protected function debug($request, $response, $type, $checkNotifications=true, $checkDebug=true)
    {
        $request = $this->excludeCredentials($request);
        if($this->debugContextAvailable() && $checkDebug)
        {
            ini_set('xdebug.var_display_max_children', -1);
            ini_set('xdebug.var_display_max_data', -1);
            ini_set('xdebug.var_display_max_depth', -1);
            $this->debugOutput = $this->preparedebugOutput($request, $response, $type);
            if(!$this->debugOutputActive) {
                echo $this->debugOutput;
                exit;
            }
        }

        if(isset($this->requestMap[self::BOXALINO_TECH_BENCHMARK]) && $this->requestMap[self::BOXALINO_TECH_BENCHMARK] == 'true' && $checkNotifications) {
            $this->addNotification('bxRequest', $request);
            $this->addNotification('bxResponse', $response);
        }
    }

    protected function prepareDebugOutput($request, $response, $type)
    {
        if(isset($this->requestMap[self::BOXALINO_TECH_DEBUG]) && $this->requestMap[self::BOXALINO_TECH_DEBUG] == 'true')
        {
            if(isset($this->requestMap['format']))
            {
                $debugOutput = [
                    json_encode($request, true),
                    json_encode($response, true)
                ];

                return implode("<br><br><br><br>", $debugOutput);
            }
            return "<pre><h1>Request {$type}</h1>" . var_export($request, true) .  "<br><h1>Choice Response</h1>" . var_export($response, true) . "</pre>";
        }

        if(isset($this->requestMap[self::BOXALINO_DEBUG_REQUEST]) && $this->requestMap[self::BOXALINO_DEBUG_REQUEST] == 'true')
        {
            return json_encode($request, true);
        }

        if(isset($this->requestMap[self::BOXALINO_DEBUG_RESPONSE]) && $this->requestMap[self::BOXALINO_DEBUG_RESPONSE] == 'true')
        {
            $logs = [];
            $bxChooseResponse = new \com\boxalino\bxclient\v1\BxChooseResponse($response, $this->chooseRequests);
            $logs[] = 'REQUEST ID - ' . $this->getRequestId() ;
            $logs[] = 'SESSION ID : PROFILE ID = ' . implode(" : ", $this->getSessionAndProfile()) . "<br><br>";
            foreach($this->chooseRequests as $inquiredRequest)
            {
                $inquiredChoice = $inquiredRequest->getChoiceId();
                if(in_array($inquiredChoice, $this->nondebugableChoices))
                {
                    continue;
                }
                $inquiredFields = $inquiredRequest->getReturnFields();
                $matchingProducts = $bxChooseResponse->getHitFieldValues($inquiredFields, $inquiredChoice, true);

                $logs[]="==============<i>Debug of Boxalino Response on <b>{$inquiredChoice}</b></i>===================";
                $logs[]= "COUNT: {$bxChooseResponse->getTotalHitCount($inquiredChoice)}";
                $logs[]="======<i><b>PRODUCTS</b></i>=====";
                foreach($matchingProducts as $id => $fieldValueMap) {
                    foreach($fieldValueMap as $fieldName => $fieldValues) {
                        $logs[] = " $fieldName: " . implode(',', $fieldValues) . "";
                    }
                    $logs[] = "</div>";
                }
                $logs[] = "</div>\n";

                if(in_array($inquiredChoice, ['search', 'navigation']))
                {
                    $logs[]="=====<i><b>FACETS</b></i>====";
                    $bxFacets = $bxChooseResponse->getFacets();
                    $fieldNames = $bxFacets->getFieldNames();
                    foreach($fieldNames as $facetField)
                    {
                        if($facetField == 'discountedPrice')
                        {
                            $logs[] = "<b>{$facetField}</b>";
                            foreach($bxFacets->getPriceRanges() as $fieldValue) {
                                $log = "value - {$bxFacets->getPriceValueParameterValue($fieldValue)}; label - {$bxFacets->getPriceValueLabel($fieldValue)}; products count - {$bxFacets->getPriceValueCount($fieldValue)}";
                                if($bxFacets->isPriceValueSelected($fieldValue)){$log .= " <b>is selected</b>";}
                                $logs[] = $log;
                            }
                            $logs[] = "<br>";
                            continue;
                        }
                        $logs[] = "facet - <b>{$facetField}</b>";
                        foreach($bxFacets->getFacetValues($facetField) as $fieldValue) {
                            $log = "value: <i>{$bxFacets->getFacetValueParameterValue($facetField, $fieldValue)}</i>; label - <i>{$bxFacets->getFacetValueLabel($facetField, $fieldValue)}</i>; count - <i>{$bxFacets->getFacetValueCount($facetField, $fieldValue)}</i>";
                            if($bxFacets->isFacetValueSelected($facetField, $fieldValue))
                            {
                                $log.= " <b>is selected</b>";
                            }
                            $logs[] = $log;
                        }
                        $extraInfo = json_encode($bxFacets->getAllFacetExtraInfo($facetField), true);
                        $logs[] = "extra info - {$extraInfo}";
                        $logs[] = "<br>";
                    }
                }
            }

            return implode("<br>", $logs);
        }

    }

    protected function excludeCredentials($request)
    {
        if(strpos(strtolower(get_class($request)), 'bundle') == false)
        {
            return $this->_excludeCredentialsByRequest($request);
        }

        foreach($request->requests as &$bundleRequest)
        {
            $bundleRequest = $this->_excludeCredentialsByRequest($bundleRequest);
        }

        return $request;
    }

    protected function _excludeCredentialsByRequest($request)
    {
        $userRecord = $request->userRecord;
        $userRecord->apiKey = $userRecord->apiSecret = "**********************";
        $request->userRecord = $userRecord;

        return $request;
    }

    public function setSendRequestId($value)
    {
        $this->sendRequestId = $value;
        return $this;
    }

    protected function uuid()
    {
        $uuid = bin2hex(random_bytes(16));
        $hyphen = chr(45);
        return substr($uuid, 0, 8).$hyphen
            .substr($uuid, 8, 4).$hyphen
            .substr($uuid,12, 4).$hyphen
            .substr($uuid,16, 4).$hyphen
            .substr($uuid,20,12);
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        if(isset($this->requestContextParameters[self::BXL_UUID_REQUEST]))
        {
            return array_pop($this->requestContextParameters[self::BXL_UUID_REQUEST]);
        }

        return "undefined";
    }

}