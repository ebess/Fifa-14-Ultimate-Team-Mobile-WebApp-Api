Connector class for mobile endpoint of Fifa 14 Ultimate Team.
Code is not commented and needs to be refactored which will come next.

Example: (also see example.php)
```php
    require_once __DIR__ . "/vendor/autoload.php";
    require_once __DIR__ . "/eahashor.php";
    require_once __DIR__ . "/mobileconnector.php";

	use Guzzle\Http\Client;
	use Guzzle\Plugin\Cookie\CookiePlugin;
	use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

	$client = new Client(null);
	$cookieJar = new ArrayCookieJar();
    $cookiePlugin = new CookiePlugin($cookieJar);
    $client->addSubscriber($cookiePlugin);

	$connector = new Mobileconnector('your@email.com', 'your_password', 'secret_answer', 'xbox', $client);
	$connector->connect();
´´´