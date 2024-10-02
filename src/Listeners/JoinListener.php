<?php

namespace Gingdev\IAskAI\Listeners;

use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Internal\Chunk;
use Gingdev\IAskAI\Internal\WebsocketFactory;

use function Amp\async;

class JoinListener
{
    public function onJoin(JoinEvent $event): void
    {
        async(function () use ($event): void {
            $client = WebsocketFactory::createForQuery($event->getQuery());
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
