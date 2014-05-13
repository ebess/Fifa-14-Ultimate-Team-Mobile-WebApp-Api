<?php


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
		$request = $this->client->get($this->urls['login']);
		$response = $request->send();
		$url = $response->getInfo('url');

		return $url;
	}

	private function loginAndGetCode($url)
	{
        $request = $this->client->post($url, array(), array(
            "email" => $this->email,
            "password" => $this->password,
            "_rememberMe" => "on",
            "rememberMe" => "on",
            "_eventId" => "submit"
        ));

        $response = $request->send();

        $this->code = $response->getBody();

        return $this;
	}

	private function enterAnswer()
	{
		$url = sprintf ($this->urls['answer'], $this->code, $this->clientSecret);
		$request = $this->client->post($url);

		$request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$response = $request->send();
		$json = $response->json();

		$this->accessToken = $json['access_token'];

        return $this;
	}

	private function gatewayMe()
	{
		$request = $this->client->get($this->urls['gateway']);

		$request->addHeader('Authorization', 'Bearer ' . $this->accessToken);
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$response = $request->send();
		$json = $response->json();

		$this->nucId = $json['pid']['pidId'];

		return $this;
	}

	private function auth()
	{
		$url = sprintf ($this->urls['auth'], $this->accessToken);
		$request = $this->client->get($url);
		$response = $request->send();

		$json = $response->json();
		$this->authCode = $json['code'];

        return $this;
	}


	private function getSid()
	{
		$request = $this->client->post($this->urls['sid']);

		$request->addHeader('X-UT-SID', '');
		$request->addHeader('X-POW-SID', '');
		$request->addHeader('Content-Type', 'application/json');
		$request->addHeader('Accept', 'text/plain, */*; q=0.01');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$request->setBody('{"isReadOnly":true,"sku":"FUT14AND","clientVersion":8,"locale":"de-DE","method":"authcode","priorityLevel":4,"identification":{"authCode":"'.$this->authCode.'","redirectUrl":"nucleus:rest"}}');

		$response = $request->send();
		$json = $response->json();

		$this->sid = $json['sid'];
		$this->pid = $response->getHeader('X-POW-SID');

        return $this;
	}

	private function utasRefreshNucId()
	{
		$url = $this->urls['utasNucId'];
		$request = $this->client->get($url);

		$request->addHeader('X-UT-SID', '');
		$request->addHeader('X-POW-SID', $this->pid);
		$request->addHeader('Easw-Session-Data-Nucleus-Id', $this->nucId);
		$request->addHeader('Content-Type', 'application/json');
		$request->addHeader('Accept', 'text/plain, */*; q=0.01');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$response = $request->send();
		$json = $response->json();

		$this->nucId = $json['userAccountInfo']['personas'][0]['personaId'];

		return $this;
	}

	private function utasAuth()
	{
		$url = $this->urls['utasAuth'];
		$request = $this->client->post($url);

		$request->addHeader('X-UT-SID', '');
		$request->addHeader('X-POW-SID', '');
		$request->addHeader('Accept', 'text/plain, */*; q=0.01');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$request->setBody('{"isReadOnly":false,"sku":"FUT14AND","clientVersion":8,"locale":"de-DE","method":"authcode","priorityLevel":4,"identification":{"authCode":"'.$this->authCode.'","redirectUrl":"nucleus:rest"},"nucleusPersonaId":'.$this->nucId.'}');

		$response = $request->send();
		$json = $response->json();

		$this->sid = $json['sid'];

		return $this;
	}

	private function utasQuestion()
	{
		$url = sprintf ($this->urls['utasQuestion'], $this->answerHash);
		$request = $this->client->post($url);

		$request->addHeader('X-UT-SID', $this->sid);
		$request->addHeader('X-POW-SID', $this->pid);
		$request->addHeader('Easw-Session-Data-Nucleus-Id', $this->nucId);
		$request->addHeader('Content-Type', 'application/json');
		$request->addHeader('Accept', 'text/plain, */*; q=0.01');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

		$response = $request->send();
		$json = $response->json();

		$this->phishingToken = $json['token'];

		return $this;
	}


    private function getWatchlist()
    {
		$url = $this->urls['utasWatchlist'];
		$request = $this->client->get($url);

		$request->addHeader('X-UT-SID', $this->sid);
		$request->addHeader('X-POW-SID', $this->pid);
		$request->addHeader('X-UT-PHISHING-TOKEN', $this->phishingToken);
		$request->addHeader('Easw-Session-Data-Nucleus-Id', $this->nucId);

		$request->addHeader('Content-Type', 'application/json');
		$request->addHeader('Accept', 'text/plain, */*; q=0.01');
		$request->addHeader('x-wap-profile', 'http://wap.samsungmobile.com/uaprof/GT-I9195.xml');
		$request->addHeader('User-Agent', 'Mozilla/5.0 (Linux; U; Android 4.2.2; de-de; GT-I9195 Build/JDQ39) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30');

        $response = $request->send();
        $json = $response->json();

        echo "watchlist count: " . count($json['auctionInfo']) . PHP_EOL;
    }

}