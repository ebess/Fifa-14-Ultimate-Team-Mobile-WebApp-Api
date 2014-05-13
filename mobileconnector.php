<?php

require_once 'forge.php';

class Mobileconnector
{
	private $email;
	private $password;
	private $answer;
	private $answerHash;
	private $platform;
	private $client;
	private $code;
	private $authCode;
	private $accessToken;
	private $sid;
	private $pid;
	private $nucId;
	private $phishingToken;
	private $clientSecret = 's92fi307abf8dcb7362cfe73cf45a06e';

	private $urls = array(
		'login' => 'https://accounts.ea.com/connect/auth?client_id=FIFA-MOBILE-COMPANION&response_type=code&display=mobile/login&scope=basic.identity+offline+signin&locale=de&prompt=login',
		'answer' => 'https://accounts.ea.com/connect/token?grant_type=authorization_code&code=%s&client_id=FIFA-MOBILE-COMPANION&client_secret=%s',
		'gateway' => 'https://gateway.ea.com/proxy/identity/pids/me',
		'auth' => 'https://accounts.ea.com/connect/auth?client_id=FOS-SERVER&redirect_uri=nucleus:rest&response_type=code&access_token=%s',
		'sid' => 'https://pas.mob.v4.easfc.ea.com:8095/pow/auth?timestamp=',
		'userdata' => 'https://pas.mob.v4.easfc.ea.com:8095/pow/user/self/tiergp/NucleusId/tiertp/%s?offset=0&count=50&_=',

		'utasNucId' => '/ut/game/fifa14/user/accountinfo?_=',
		'utasAuth' => '/ut/auth?timestamp=',
		'utasQuestion' => '/ut/game/fifa14/phishing/validate?answer=%s&timestamp=',
		'utasWatchlist' => '/ut/game/fifa14/watchlist',
	);

	public function __construct($email, $password, $answer, $platform, $client)
	{
		$this->email = $email;
		$this->password = $password;
		$this->answer = $answer;
		$this->platform = $platform;
		$this->answerHash = EAHashor::hash($answer);
		$this->client = $client;


		$utasServer = ($platform == 'ps3') ? 'https://utas.s2.fut.ea.com' : 'https://utas.fut.ea.com';
		$this->urls['utasNucId'] = $utasServer . $this->urls['utasNucId'];
		$this->urls['utasAuth'] = $utasServer . $this->urls['utasAuth'];
		$this->urls['utasQuestion'] = $utasServer . $this->urls['utasQuestion'];
		$this->urls['utasWatchlist'] = $utasServer . $this->urls['utasWatchlist'];
	}

	private function getForge($url, $method) 
	{
		return new Forge($this->client, $url, $method);
	}

	public function connect()
	{
		$url = $this->getLoginUrl();

		$this
			->loginAndGetCode($url)
			->enterAnswer()
			->gatewayMe()
			->auth()
			->getSid()
			->utasRefreshNucId()
			->auth()
			->utasAuth()
			->utasQuestion();

		// check watchlist
		$this->getWatchlist();
	}

	private function getLoginUrl()
	{
		$forge = $this->getForge($this->urls['login'], 'get');
		$data = $forge->sendRequest();

		return $data['response']->getInfo('url');
	}

	private function loginAndGetCode($url)
	{
		$forge = $this->getForge($url, 'post');
		$this->code = $forge
			->setBody(array(
	            "email" => $this->email,
	            "password" => $this->password,
	            "_rememberMe" => "on",
	            "rememberMe" => "on",
	            "_eventId" => "submit"
	        ))
	        ->getBody();

        return $this;
	}

	private function enterAnswer()
	{
		$url = sprintf ($this->urls['answer'], $this->code, $this->clientSecret);

		$forge = $this->getForge($url, 'post');
		$json = $forge
			->addHeader('Content-Type', 'application/x-www-form-urlencoded')
			->getJson();

		$this->accessToken = $json['access_token'];

        return $this;
	}

	private function gatewayMe()
	{
		$forge = $this->getForge($this->urls['gateway'], 'get');
		$json = $forge
			->addHeader('Authorization', 'Bearer ' . $this->accessToken)
			->getJson();

		$this->nucId = $json['pid']['pidId'];

		return $this;
	}

	private function auth()
	{
		$url = sprintf ($this->urls['auth'], $this->accessToken);
		$forge = $this->getForge($url, 'get');
		$json = $forge->getJson();

		$this->authCode = $json['code'];

        return $this;
	}


	private function getSid()
	{
		$forge = $this->getForge($this->urls['sid'], 'post');
		$data = $forge
			->setSid('')
			->setPid('')
			->setBody(array(
				'isReadOnly' 		=> true,
				'sku' 				=> 'FUT14AND',
				'clientVersion' 	=> 8,
				'locale'			=> 'de-DE',
				'method'			=> 'authcode',
				'priorityLevel'		=> 4,
				'identification'	=> array(
					'authCode' 		=> $this->authCode,
					'redirectUrl'	=> 'nucleus:rest'
				),
			), true)
			->sendRequest();

		$json = $data['response']->json();
		$this->sid = $json['sid'];
		$this->pid = $data['response']->getHeader('X-POW-SID');


        return $this;
	}

	private function utasRefreshNucId()
	{
		$forge = $this->getForge($this->urls['utasNucId'], 'get');
		$json = $forge
			->setSid('')
			->setPid($this->pid)
			->setNucId($this->nucId)
			->getJson();

		$this->nucId = $json['userAccountInfo']['personas'][0]['personaId'];

		return $this;
	}

	private function utasAuth()
	{
		$forge = $this->getForge($this->urls['utasAuth'], 'post');
		$json = $forge
			->setSid('')
			->setPid('')
			->setBody(array(
				'isReadOnly' 		=> true,
				'sku' 				=> 'FUT14AND',
				'clientVersion' 	=> 8,
				'locale'			=> 'de-DE',
				'method'			=> 'authcode',
				'priorityLevel'		=> 4,
				'identification'	=> array(
					'authCode' 		=> $this->authCode,
					'redirectUrl'	=> 'nucleus:rest'
				),
				'nucleusPersonaId'	=> $this->nucId
			), true)
			->getJson();

		$this->sid = $json['sid'];

		return $this;
	}

	private function utasQuestion()
	{
		$url = sprintf ($this->urls['utasQuestion'], $this->answerHash);

		$forge = $this->getForge($url, 'post');
		$json = $forge
			->setSid($this->sid)
			->setPid($this->pid)
			->setNucId($this->nucId)
			->setSid($this->sid)
			->getJson();

		$this->phishingToken = $json['token'];

		return $this;
	}

    private function getWatchlist()
    {
    	$forge = $this->getForge($this->urls['utasWatchlist'], 'get');
    	$json = $forge
			->setSid($this->sid)
			->setPid($this->pid)
			->setNucId($this->nucId)
			->setPhishing($this->phishingToken)
			->getJson();

        echo "watchlist count: " . count($json['auctionInfo']) . PHP_EOL;
    }

}