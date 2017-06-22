<?php
namespace Thrift\Transport;

use Thrift\Transport\TTransport;
use Thrift\Exception\TTransportException;
use Thrift\Factory\TStringFuncFactory;

class P13nTHttpClient extends THttpClient {

  protected $authorizationString;
  
  /**
   * Opens and sends the actual request over the HTTP connection
   *
   * @throws TTransportException if a writing error occurs
   */
  public function flush() {
	// God, PHP really has some esoteric ways of doing simple things.
	$host = $this->host_.($this->port_ != 80 ? ':'.$this->port_ : '');

	$headers = array('Host: '.$host,
					 'Accept: application/x-thrift',
					 'User-Agent: PHP/THttpClient',
					 'Content-Type: application/x-thrift',
					 'Content-Length: '.TStringFuncFactory::create()->strlen($this->buf_),
					 'Authorization: Basic '.$this->authorizationString);

	$options = array('method' => 'POST',
					 'header' => implode("\r\n", $headers),
					 'max_redirects' => 1,
					 'content' => $this->buf_);
	if ($this->timeout_ > 0) {
	  $options['timeout'] = $this->timeout_;
	}
	$this->buf_ = '';

	$contextid = stream_context_create(array('http' => $options));
	$this->handle_ = @fopen($this->scheme_.'://'.$host.$this->uri_, 'r', false, $contextid);

	// Connect failed?
	if ($this->handle_ === FALSE) {
	  $this->handle_ = null;
	  $error = 'P13nTHttpClient: Could not connect to '.$host.$this->uri_;
	  throw new TTransportException($error, TTransportException::NOT_OPEN);
	}
  }

  public function setAuthorization($username, $password) {
    $this->authorizationString = base64_encode($username.':'.$password);
  }
}
