<?php

namespace Gingdev\IAskAI\Internal;

interface IResponse
{
    /**
     * @return iterable<string>
     */
    public function stream(): iterable;

    public function get(): string;
}
