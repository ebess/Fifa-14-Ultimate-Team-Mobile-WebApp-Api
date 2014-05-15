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
     * @param \Guzzle\Http\Client $client
     * @param string $email
     * @param string $password
     * @param string $answer
     * @param string $platform
     */
    public function __construct($client, $email, $password, $answer, $platform)
    {
        $this->client = $client;
        $this->email = $email;
        $this->password = $password;
        $this->answer = $answer;
        $this->platform = $platform;
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
            $this->connector = new $class($this->client, $this->email, $this->password, $this->answer, $this->platform);

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