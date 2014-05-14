<?php

/**
 * Class Request_Forge
 */
class Request_Forge
{
    /**
     * @var Guzzle\Http\Client
     */
    private $client;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var null|string
     */
    private $sid = null;

    /**
     * @var null|string
     */
    private $pid = null;

    /**
     * @var null|string
     */
    private $phishing = null;

    /**
     * @var null|string
     */
    private $nucId = null;

    /**
     * @var string[]
     */
    private $headers = array();

    /**
     * @var string[]
     */
    private $removedHeaders = array();

    /**
     * @var mixed
     */
    private $body = null;

    /**
     * @var bool
     */
    private $bodyAsString = false;

    /**
     * creates a request forge for given url and method
     *
     * @param Guzzle\Http\Client $client
     * @param string $url
     * @param string $method
     */
    public function __construct($client, $url, $method)
	{
		$this->client = $client;
		$this->url = $url;
		$this->method = $method;
	}

    /**
     * EA: session id
     *
     * @param string $sid
     * @return $this
     */
    public function setSid($sid)
	{
		$this->sid = $sid;

		return $this;
	}

    /**
     * EA: pow id
     *
     * @param string $pid
     * @return $this
     */
    public function setPid($pid)
	{
		$this->pid = $pid;

		return $this;
	}

    /**
     * EA: phishing token
     *
     * @param string $phishing
     * @return $this
     */
    public function setPhishing($phishing)
	{
		$this->phishing = $phishing;

		return $this;
	}

    /**
     * EA: nucleus id
     *
     * @param string $nucId
     * @return $this
     */
    public function setNucId($nucId)
	{
		$this->nucId = $nucId;

		return $this;
	}

    /**
     * data will be applied on the requests, if marked as string, the data will be set as a json string
     *
     * @param mixed $data
     * @param bool $asString
     * @return $this
     */
    public function setBody($data, $asString  = false)
	{
		$this->bodyAsString = $asString;
		$this->body = $data;

		return $this;
	}

    /**
     * adds a header to the requests
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;

		return $this;
	}

    /**
     * blacklists a header which will be removed before sending the request
     *
     * @param string $name
     * @return $this
     */
    public function removeHeader($name)
	{
		if (!in_array($name, $this->removedHeaders)) {
			$this->removedHeaders[] = $name;	
		}

		return $this;
	}

    /**
     * sends request and returns the answer as json
     *
     * @return array
     */
    public function getJson()
	{
		$data = $this->sendRequest();

		return $data['response']->json();
	}

    /**
     * sends request and returns the received body
     *
     * @return string
     */
    public function getBody()
	{
		$data = $this->sendRequest();

		return $data['response']->getBody();
	}

    /**
     * sends the requests and returns the request itself and the response object
     *
     * @return Guzzle\Http\Message\AbstractMessage[]
     */
    public function sendRequest()
	{
		$request = $this->forgeRequestWithCommonHeaders();

		$this
			->applyHeaders($request)
			->applyBodyString($request);

		$response = $request->send();

		return array(
			'request' => $request,
			'response' => $response
		);
	}

    /**
     * applies set headers to the request object
     * adds headers, remove headers and adds - if set - the ea specific requests
     *
     * @param Guzzle\Http\Message\Request $request
     * @return $this
     */
    private function applyHeaders($request)
	{	
		// add headers
		foreach ($this->headers as $name => $val) {
			$request->removeHeader($name);
			$request->addHeader($name, $val);
		}

		// fut specific headers
		if ($this->sid !== null) {
			$request->addHeader('X-UT-SID', $this->sid);
		}

		if ($this->pid !== null) {
			$request->addHeader('X-POW-SID', $this->pid);
		}

		if ($this->phishing !== null) {
			$request->addHeader('X-UT-PHISHING-TOKEN', $this->phishing);
		}

		if ($this->nucId !== null) {
			$request->addHeader('Easw-Session-Data-Nucleus-Id', $this->nucId);
		}

		// remove headers
		foreach ($this->removedHeaders as $name) {
			$request->removeHeader($name);
		}

		return $this;
	}

    /**
     * adds the body as a json string to the request body
     *
     * @param Guzzle\Http\Message\Request $request
     * @return $this
     */
    private function applyBodyString($request)
	{
		if ($this->bodyAsString) {
			$request->setBody(json_encode($this->body));
		}

		return $this;
	}

    /**
     * creates a request with common headers which needed for the connector request
     *
     * @return Guzzle\Http\Message\Request
     */
    private function forgeRequestWithCommonHeaders()
	{
		$request = $this->client->{$this->method}($this->url);

		if ($this->body !== null && $this->bodyAsString == false) {
			$query = $request->getQuery();
			foreach ($this->body as $name => $value) {
				$query->set($name, $value);
			}
		}

		$request->addHeader('Content-Type', 'application/json');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');
		$request->addHeader('Accept', 'application/json, text/plain, */*; q=0.01');

		return $request;
	}


}