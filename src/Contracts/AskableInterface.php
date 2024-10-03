<?php

namespace Gingdev\IAskAI\Contracts;

use Amp\ByteStream\ReadableStream;

/**
 * @see https://iask.ai/
 *
 * @phpstan-type ModeType = 'question'|'academic'|'fast'|'forums'|'wiki'|'advanced'
 * @phpstan-type DetailLevelType = 'concise'|'detailed'|'comprehensive'
 * @phpstan-type OptionsType = array{ detail_level: DetailLevelType }
 * @phpstan-type QueryType = array{ q: string, mode: ModeType, options?: OptionsType }|string
 */
interface AskableInterface
{
    /**
     * @param QueryType $query
     */
    public function ask(
        array|string $query,
    ): ReadableStream;
}
