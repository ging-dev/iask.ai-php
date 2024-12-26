<?php

namespace Gingdev\IAskAI\Providers;

use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\Rfc6455Connector;
use Amp\Websocket\Client\WebsocketHandshake;
use Gingdev\IAskAI\Events\JoinEvent;
use League\Uri\Uri;
use Revolt\EventLoop;

use function Gingdev\IAskAI\Internal\inspect;
use function Gingdev\IAskAI\Internal\parseMessage;

final class IAskProvider
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    public static function handle(JoinEvent $event): void
    {
        $httpClient = (new HttpClientBuilder())
            ->interceptNetwork(new CookieInterceptor(new LocalCookieJar()))
            ->build()
        ;
        $response = $httpClient->request(new Request(
            Uri::new(static::BASE_URL)->withQuery($event->getQuery())
        ));
        [$joinMessage, $csrfToken] = inspect($response);
        $handshake = (new WebsocketHandshake(static::WEBSOCKET_URL))
            ->withQueryParameter('_csrf_token', $csrfToken)
            ->withQueryParameter('vsn', '2.0.0')
        ;
        $wsClient = (new Rfc6455Connector(httpClient: $httpClient))
            ->connect($handshake);
        $wsClient->sendText($joinMessage);

        EventLoop::queue(function () use ($wsClient, $event): void {
            $continue = true;
            $sink = $event->getPipe()->getSink();
            while ($continue && $message = $wsClient->receive()) {
                [$content, $continue] = parseMessage($message);
                if ($content) {
                    $sink->write($content);
                }
            }
            $sink->close();
            $wsClient->close();
        });
    }
}
