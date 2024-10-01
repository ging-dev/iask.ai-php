<?php

namespace Gingdev\IAskAI\Events;

use Amp\ByteStream\Pipe;
use Gingdev\IAskAI\Contracts\AskableInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @phpstan-import-type QueryType from AskableInterface
 */
class JoinEvent extends Event
{
    private Pipe $pipe;

    /**
     * @param QueryType $query
     */
    public function __construct(
        private array|string $query,
    ) {
        $this->pipe = new Pipe(0);
    }

    public function getQuery(): string
    {
        return http_build_query(
            is_array($this->query)
            ? $this->query
            : ['q' => $this->query]
        );
    }

    public function getPipe(): Pipe
    {
        return $this->pipe;
    }
}
