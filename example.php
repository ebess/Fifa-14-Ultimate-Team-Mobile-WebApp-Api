<?php



    require_once __DIR__ . "/vendor/autoload.php";
    require_once __DIR__ . "/eahashor.php";
    require_once __DIR__ . "/forge.php";
    require_once __DIR__ . "/mobileconnector.php";


	use Guzzle\Http\Client;
	use Guzzle\Plugin\Cookie\CookiePlugin;
	use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
	

	$client = new Client(null);
	$cookieJar = new ArrayCookieJar();
    $cookiePlugin = new CookiePlugin($cookieJar);
    $client->addSubscriber($cookiePlugin);

    start:
    try {
		// platform needs to be ps3 or something else (xbox, pc etc)
		$connector = new Mobileconnector('your@email.com', 'your_password', 'secret_answer', 'platform', $client);
		$connector->connect();
    } catch (Exception $e) {
    	// server down, gotta retry
    	if (preg_match("/service unavailable/mi", $e->getMessage())) {
    		echo "EA Server down, retry! " . PHP_EOL;
    		sleep(1);
    		goto start;
    	} else {
    		die('Failed to login' . PHP_EOL);
    	}
    }