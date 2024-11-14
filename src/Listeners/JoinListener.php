<?php

namespace Gingdev\IAskAI\Listeners;

use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Internal\Chunk;
use Gingdev\IAskAI\Internal\WebsocketFactory;
use Revolt\EventLoop;

class JoinListener
{
    public function onJoin(JoinEvent $event): void
    {
        EventLoop::queue(function () use ($event): void {
            $client = WebsocketFactory::createForQuery($event->getQuery());
            $sink = $event->getPipe()->getSink();
            while ($message = $client->receive()) {
                $chunk = Chunk::from($message);
                if ('' !== $chunk->content) {
                    $sink->write($chunk->content);
                }
                if ($chunk->stop) {
                    break;
                }
            }
            $sink->close();
            $client->close();
        });
    }
}
