<?php

namespace Gingdev\IAskAI\Listeners;

use Amp\Websocket\Client\WebsocketHandshake;
use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Internal\Chunk;
use Gingdev\IAskAI\Internal\Inspector;
use League\Uri\Uri;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Amp\async;
use function Amp\Websocket\Client\connect;

class JoinListener
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    public function __construct(private HttpClientInterface $client)
    {
    }

    public function onJoin(JoinEvent $event): void
    {
        async(function () use ($event): void {
            [$cookie, $csrftoken, $message] = Inspector::inspect(
                $this->client,
                Uri::new(static::BASE_URL)->withQuery($event->getQuery()),
            );
            $handshake = (new WebsocketHandshake(static::WEBSOCKET_URL))
                ->withQueryParameter('_csrf_token', $csrftoken)
                ->withQueryParameter('vsn', '2.0.0')
                ->withHeader('Cookie', $cookie)
            ;
            $client = connect($handshake);
            $client->sendText($message);
            $sink = $event->getPipe()->getSink();
            do {
                $chunk = Chunk::from($client->receive());
                if ($chunk->content->isEmpty()) {
                    continue;
                }
                $sink->write($chunk->content->toString());
            } while ($chunk->continue);
            $sink->close();
            $client->close();
        });
    }
}
