<?php

namespace Gingdev\IAskAI\Internal;

interface Askable
{
    /**
     * @param 'question'|'academic'|'fast'|'forums'|'wiki'|'advanced' $mode
     * @param 'concise'|'detailed'|'comprehensive'                    $level
     */
    public function ask(
        string $input,
        string $mode,
        string $level,
    ): IResponse;
}
