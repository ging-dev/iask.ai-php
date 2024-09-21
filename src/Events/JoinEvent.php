<?php

namespace Gingdev\IAskAI\Events;

use Amp\Pipeline\Queue;
use Gingdev\IAskAI\Contracts\AskableInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @phpstan-import-type QueryType from AskableInterface
 */
class JoinEvent extends Event
{
    /** @var Queue<string> */
    private Queue $queue;

    /**
     * @param QueryType $query
     */
    public function __construct(
        private array|string $query,
    ) {
        $this->queue = new Queue();
    }

    public function getQuery(): string
    {
        return http_build_query(
            is_array($this->query)
            ? $this->query
            : ['q' => $this->query]
        );
    }

    /**
     * @return Queue<string>
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }
}
