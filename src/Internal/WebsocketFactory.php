<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\Rfc6455Connector;
use Amp\Websocket\Client\WebsocketConnection;
use Amp\Websocket\Client\WebsocketHandshake;
use League\Uri\Uri;

/**
 * @internal
 */
final class WebsocketFactory
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    public static function createForQuery(string $query): WebsocketConnection
    {
        $httpClient = (new HttpClientBuilder())
            ->interceptNetwork(new CookieInterceptor(new LocalCookieJar()))
            ->build()
        ;
        $response = $httpClient->request(new Request(
            Uri::new(static::BASE_URL)->withQuery($query)
        ));
        $inspector = Inspector::inspect($response);
        $handshake = (new WebsocketHandshake(static::WEBSOCKET_URL))
            ->withQueryParameter('_csrf_token', $inspector->getCsrfToken())
            ->withQueryParameter('vsn', '2.0.0')
        ;
        $wsClient = (new Rfc6455Connector(httpClient: $httpClient))
            ->connect($handshake);
        $wsClient->sendText($inspector->getJoinMessage());

        return $wsClient;
    }
}
