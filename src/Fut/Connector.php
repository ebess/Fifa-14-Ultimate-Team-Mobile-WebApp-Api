<?php

namespace Fut;

use Fut\Request\Forge;
use Fut\Connector\WebApp;
use Fut\Connector\Mobile;

/**
 * connector class wrapper
 *
 * Class Connector
 * @package Fut
 */
class Connector
{
    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $answerHash;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $platform;

    /**
     * @var \Guzzle\Http\Client
     */
    protected $client;

    /**
     * @var \Guzzle\Plugin\Cookie\CookiePlugin
     */
    protected $cookiePlugin;

    /**
     * @var string
     */
    protected $answer;

    /**
     * @var string[]
     */
    protected $endpoints = array(
        'WebApp', 'Mobile'
    );

    /**
     * @var null|\Fut\Connector\Generic
     */
    protected $connector = null;

    /**
     * creates wrapper connector
     *
     * @param string $email
     * @param string $password
     * @param string $answer
     * @param string $platform
     */
    public function __construct($email, $password, $answer, $platform)
    {
        $this->email = $email;
        $this->password = $password;
        $this->answer = $answer;
        $this->platform = $platform;
        Request\Forge::setPlatform($this->platform);
    }

    /**
     * @param \Guzzle\Http\Client $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @param \Guzzle\Plugin\Cookie\CookiePlugin $cookiePlugin
     * @return $this
     */
    public function setCookiePlugin($cookiePlugin)
    {
        $this->cookiePlugin = $cookiePlugin;

        return $this;
    }

    /**
     * connect with the appropriate connector
     *
     * @param string $endpoint
     * @return null
     */
    public function connect($endpoint = 'WebApp')
    {
        if (in_array($endpoint, $this->endpoints, true)) {
            // set forge endpoint
            Forge::setEndpoint($endpoint);
            $class = "Fut\\Connector\\" . $endpoint;
            $this->connector = new $class($this->email, $this->password, $this->answer, $this->platform);
            $this->connector
                ->setClient($this->client)
                ->setCookiePlugin($this->cookiePlugin);

            $this->connector->connect();
        }

        return $this;
    }

    /**
     * returns needed data for login again
     *
     * @return string[]
     */
    public function export()
    {
        return $this->connector->exportLoginData();
    }
}