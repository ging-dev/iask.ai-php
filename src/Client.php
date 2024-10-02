<?php

namespace Gingdev\IAskAI;

use Amp\ByteStream\ReadableStream;
use Gingdev\IAskAI\Contracts\AskableInterface;
use Gingdev\IAskAI\Events\JoinEvent;
use Gingdev\IAskAI\Listeners\JoinListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class Client implements AskableInterface
{
    private function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public static function create(
        ?EventDispatcherInterface $dispatcher = null
    ): AskableInterface {
        $dispatcher = $dispatcher ?: new EventDispatcher();
        $dispatcher->addListener(
            JoinEvent::class,
            (new JoinListener())->onJoin(...)
        );

        return new self($dispatcher);
    }

    public function ask(array|string $query): ReadableStream
    {
        $event = $this->dispatcher->dispatch(new JoinEvent($query));

        return $event->getPipe()->getSource();
    }
}
