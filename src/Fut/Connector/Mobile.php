<?php

namespace Fut\Connector;

/**
 * connector used to connect as a mobile device
 *
 * Class Connector_Mobile
 */
class Mobile extends Generic
{
    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $authCode;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $pid;

    /**
     * @var string
     */
    protected $clientSecret = 's92fi307abf8dcb7362cfe73cf45a06e';

    /**
     * @var string[]
     */
    protected $urls = array(
        'login'         => 'https://accounts.ea.com/connect/auth?client_id=FIFA-MOBILE-COMPANION&response_type=code&display=mobile/login&scope=basic.identity+offline+signin&locale=de&prompt=login',
        'answer'        => 'https://accounts.ea.com/connect/token?grant_type=authorization_code&code=%s&client_id=FIFA-MOBILE-COMPANION&client_secret=%s',
        'gateway'       => 'https://gateway.ea.com/proxy/identity/pids/me',
        'auth'          => 'https://accounts.ea.com/connect/auth?client_id=FOS-SERVER&redirect_uri=nucleus:rest&response_type=code&access_token=%s',
        'sid'           => 'https://pas.mob.v4.easfc.ea.com:8095/pow/auth?timestamp=',

        'utasNucId'     => '/ut/game/fifa14/user/accountinfo?_=',
        'utasAuth'      => '/ut/auth?timestamp=',
        'utasQuestion'  => '/ut/game/fifa14/phishing/validate?answer=%s&timestamp=',
        'utasWatchlist' => '/ut/game/fifa14/watchlist',
    );

    /**
     * creates a connector with given credentials
     *
     * @param string $email
     * @param string $password
     * @param string $answer
     * @param string $platform
     */
    public function __construct($email, $password, $answer, $platform)
    {
        parent::__construct($email, $password, $answer, $platform);

        $utasServer = ($platform == 'xbox360') ? 'https://utas.fut.ea.com' : 'https://utas.s2.fut.ea.com';
        $this->urls['utasNucId'] = $utasServer . $this->urls['utasNucId'];
        $this->urls['utasAuth'] = $utasServer . $this->urls['utasAuth'];
        $this->urls['utasQuestion'] = $utasServer . $this->urls['utasQuestion'];
        $this->urls['utasWatchlist'] = $utasServer . $this->urls['utasWatchlist'];
    }

    /**
     * connects to the mobile api
     */
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

        return $this;
    }

    /**
     * exports needed data to reconnect again with actually login
     *
     * @return string[]
     */
    public function exportLoginData()
    {
        return array(
            'nucleusId' => $this->nucId,
            'sessionId' => $this->sid,
            'phishingToken' => $this->phishingToken,
            'pid' => $this->pid,
            'cookies' => $this->cookiePlugin,
        );
    }

    /**
     * gets the url where you need to send the login request
     *
     * @return string
     */
    private function getLoginUrl()
    {
        $forge = $this->getForge($this->urls['login'], 'get');
        $data = $forge->sendRequest();

        return $data['response']->getInfo('url');
    }

    /**
     * login request
     *
     * @param string $url
     * @return $this
     */
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

    /**
     * get access token request
     *
     * @return $this
     */
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

    /**
     * gateway registration request
     *
     * @return $this
     */
    private function gatewayMe()
    {
        $forge = $this->getForge($this->urls['gateway'], 'get');
        $json = $forge
            ->addHeader('Authorization', 'Bearer ' . $this->accessToken)
            ->getJson();

        $this->nucId = $json['pid']['pidId'];

        return $this;
    }

    /**
     * auth to the mobile api
     *
     * @return $this
     */
    private function auth()
    {
        $url = sprintf ($this->urls['auth'], $this->accessToken);
        $forge = $this->getForge($url, 'get');
        $json = $forge->getJson();

        $this->authCode = $json['code'];

        return $this;
    }

    /**
     * gets the session id request
     *
     * @return $this
     */
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
        $this->pid = (string) $data['response']->getHeader('X-POW-SID');


        return $this;
    }

    /**
     * refresh the nucleus at the utas server
     *
     * @return $this
     */
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

    /**
     * auth request to the utas server
     *
     * @param int $retried
     * @return $this
     */
    private function utasAuth($retried = 0)
    {
        $json = array();
        try {
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

        } catch (Exception $e) {
            // server down, gotta retry
            if ($retried < 5 && preg_match("/service unavailable/mi", $e->getMessage())) {
                return $this->utasAuth($retried++);
            // if retried to many times or other exception, delegate exception
            } else {
                throw $e;
            }
        }

        $this->sid = $json['sid'];

        return $this;
    }

    /**
     * answer secret question
     *
     * @return $this
     */
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

}