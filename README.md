Connector class for mobile endpoint of Fifa 14 Ultimate Team.
Also you can use composer to install the connectors

 composer.json
```json
    require {
        "fut/connectors": "dev-master"
    }
```

Example: (also see example.php)
```php
    require_once __DIR__ . "/vendor/autoload.php";
    require_once __DIR__ . "/autoload.php";

    use Guzzle\Http\Client;
    use Guzzle\Plugin\Cookie\CookiePlugin;
    use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;

    $client = new Client(null);
    $cookieJar = new ArrayCookieJar();
    $cookiePlugin = new CookiePlugin($cookieJar);
    $client->addSubscriber($cookiePlugin);

    try {

        // platform needs to be ps3 or something else (xbox, pc etc)
        $connector = new Fut\Connector('your@email.com', 'your_password', 'secret_answer', 'platform');
        $export = $connector
            ->setClient($client)
            ->setCookiePlugin($cookiePlugin)
            ->connect('Mobile') // there are 'Mobile' and 'WebApp' available
            ->export();

    } catch(Exception $e) {
        die('login failed' . PHP_EOL);
    }

    // example for playstation accounts to get the credits
    // 3. parameter of the forge factory is the actual real http method
    // 4. parameter is the overridden method for the webapp headers
    $forge = Fut\Request\Forge::getForge($client, '/ut/game/fifa14/user/credits', 'post', 'get');
    $json = $forge
        ->setNucId($export['nucleusId'])
        ->setSid($export['sessionId'])
        ->setPhishing($export['phishingToken'])
        ->getJson();

    echo "you have " . $json['credits'] . " coins" . PHP_EOL;

    // search player : ronaldo
    $assetId = 20801;

    $forge = \Fut\Request\Forge::getForge($client, '/ut/game/fifa14/transfermarket', 'post', 'get');
    $json = $forge
        ->setNucId($export['nucleusId'])
        ->setSid($export['sessionId'])
        ->setPhishing($export['phishingToken'])
        ->setPid($export['pid'])
        ->setBody(array(
            'maskedDefId'   => $assetId,
            'start'         => 0,
            'num'           => 5
        ))->getJson();


    echo "search for ronaldo (" . count($json['auctionInfo']) . ")" . PHP_EOL . PHP_EOL;

    foreach ($json['auctionInfo'] as $auction) {
        echo "auction: " . PHP_EOL;
        echo " - current bid: " . $auction['currentBid'] . PHP_EOL;
        echo " - buy now price: " . $auction['buyNowPrice'] . PHP_EOL;
        echo " - rating: " . $auction['itemData']['rating'] . PHP_EOL;
        echo " - expires: ~" . round($auction['expires']/60, 0) . " minutes" . PHP_EOL . PHP_EOL;
    }
```
