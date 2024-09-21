<?php

namespace Gingdev\IAskAI;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Gingdev\IAskAI\Contracts\AskableInterface;
use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Listeners\JoinListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client implements AskableInterface
{
    private function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function create(
        ?HttpClientInterface $client = null,
        ?EventDispatcherInterface $dispatcher = null
    ): AskableInterface {
        $dispatcher = $dispatcher ?: new EventDispatcher();
        $listener = new JoinListener($client ?: HttpClient::create());
        $dispatcher->addListener(JoinEvent::class, $listener->onJoin(...));

        return new self($dispatcher);
    }

    public function ask(array|string $query): ReadableStream
    {
        $event = $this->dispatcher->dispatch(new JoinEvent($query));

        return new ReadableIterableStream($event->getQueue()->pipe());
    }
}
