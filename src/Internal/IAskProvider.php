<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Http\Client\Cookie\CookieInterceptor;
use Amp\Http\Client\Cookie\LocalCookieJar;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Websocket\Client\Rfc6455Connector;
use Amp\Websocket\Client\WebsocketHandshake;
use Gingdev\IAskAI\Events\JoinEvent;
use League\HTMLToMarkdown\HtmlConverter;
use League\Uri\Uri;
use Revolt\EventLoop;

/**
 * @internal
 * @param mixed[] $value
 */
function dot(string $keys, array $value): string|false
{
    foreach (explode('.', $keys) as $key) {
        if (!array_key_exists($key, $value)) {
            return false;
        }
        $value = $value[$key];
    }

    return $value;
}

/**
 * @internal
 */
final class IAskProvider
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';
    public const END = '2.1.4.4';

    public static function handle(JoinEvent $event): void
    {
        $httpClient = (new HttpClientBuilder())
            ->interceptNetwork(new CookieInterceptor(new LocalCookieJar()))
            ->build()
        ;
        $response = $httpClient->request(new Request(
            Uri::new(static::BASE_URL)->withQuery($event->getQuery())
        ));
        $inspector = Inspector::inspect($response);
        $handshake = (new WebsocketHandshake(static::WEBSOCKET_URL))
            ->withQueryParameter('_csrf_token', $inspector->getCsrfToken())
            ->withQueryParameter('vsn', '2.0.0')
        ;
        $wsClient = (new Rfc6455Connector(httpClient: $httpClient))
            ->connect($handshake);
        $wsClient->sendText($inspector->getJoinMessage());

        EventLoop::queue(function () use ($wsClient, $event): void {
            $sink = $event->getPipe()->getSink();
            $converter = new HtmlConverter();
            while ($message = $wsClient->receive()) {
                $data = json_decode($message->buffer(), true);
                $diff = array_pop($data);
                $content = '';
                $stop = false !== dot(self::END, $diff);
                if ($cache = dot('response.rendered.'.self::END, $diff)) {
                    $content = $converter->convert($cache);
                    $stop = true;
                }
                if ($chunk = dot('e.0.1.data', $diff)) {
                    $content = str_replace('<br/>', PHP_EOL, $chunk);
                }
                if ('' !== $content) {
                    $sink->write($content);
                }
                if ($stop) {
                    break;
                }
            }
            $sink->close();
            $wsClient->close();
        });
    }
}
