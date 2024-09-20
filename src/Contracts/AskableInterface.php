<?php

namespace Gingdev\IAskAI\Contracts;

use Amp\ByteStream\ReadableStream;

/**
 * @see https://iask.ai/
 *
 * @phpstan-type ModeType = 'question'|'academic'|'fast'|'forums'|'wiki'|'advanced'
 * @phpstan-type DetailLevelType = 'concise'|'detailed'|'comprehensive'
 * @phpstan-type OptionsType = array{ detail_level: DetailLevelType }
 */
interface AskableInterface
{
    /**
     * @param array{ q: string, mode: ModeType, options?: OptionsType }|string $input
     */
    public function ask(
        array|string $input
    ): ReadableStream;
}
