<?php
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/autoload.php";

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Subscriber\Cookie as CookieSubscriber;
use Fut\Connector;
use Fut\Request\Forge;

/**
 * the connector will not export your cookie jar anymore
 * keep a reference on this object somewhere to inject it on reconnecting
 */
$client = new Client();
$cookieJar = new CookieJar();
$cookieSubscriber = new CookieSubscriber($cookieJar);
$client->getEmitter()->attach($cookieSubscriber);

try {

    /**
     * there are two endpoints at the the moment
     *
     * playstation: Forge::PLATFORM_PLAYSTATION
     * xbox: Forge::PLATFORM_XBOX
     *
     * also you can set two different endpoints
     *
     * mobile: Forge::ENDPOINT_MOBILE
     * webapp: Forge::ENDPOINT_WEBAPP
     *
     */
    $connector = new Connector(
        $client,
        'your@email.com',
        'your_password',
        'secret_answer',
        Forge::PLATFORM_PLAYSTATION,
        Forge::ENDPOINT_MOBILE
    );

    $export = $connector
        ->connect()
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
