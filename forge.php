<?php


class Forge 
{
	private $client;
	private $url;
	private $method;
	private $sid = null;
	private $pid = null;
	private $phishing = null;
	private $nucId = null;
	private $headers = array();
	private $removedHeaders = array();
	private $body = null;
	private $bodyAsString = false;

	public function __construct($client, $url, $method) 
	{
		$this->client = $client;
		$this->url = $url;
		$this->method = $method;
	}

	public function setSid($sid) 
	{
		$this->sid = $sid;

		return $this;
	}

	public function setPid($pid) 
	{
		$this->pid = $pid;

		return $this;
	}

	public function setPhishing($phishing) 
	{
		$this->phishing = $phishing;

		return $this;
	}

	public function setNucId($nucId) 
	{
		$this->nucId = $nucId;

		return $this;
	}

	public function setBody($data, $asString  = false) 
	{
		$this->bodyAsString = $asString;
		$this->body = $data;

		return $this;
	}

	public function setBodyString($string) 
	{
		$this->bodyString = $string;

		return $this;
	}

	public function addHeader($name, $value) 
	{
		$this->headers[$name] = $value;

		return $this;
	}

	public function removeHeader($name) 
	{
		if (!in_array($name, $this->removedHeaders)) {
			$this->removedHeaders[] = $name;	
		}

		return $this;
	}

	public function getJson()
	{
		$data = $this->sendRequest();

		return $data['response']->json();
	}

	public function getBody()
	{
		$data = $this->sendRequest();

		return $data['response']->getBody();
	}

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

	private function applyBodyString($request) 
	{
		if ($this->bodyAsString) {
			$request->setBody(json_encode($this->body));
		}

		return $this;
	}

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