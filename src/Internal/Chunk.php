<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Websocket\WebsocketMessage;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * @internal
 */
final class Chunk
{
    private function __construct(
        public string $content,
        public bool $stop,
    ) {
    }

    public static function from(WebsocketMessage $message): self
    {
        $data = json_decode($message->buffer(), true);
        $diff = array_pop($data);
        $content = '';
        $stop = isset($diff[2][1][4][4]);
        if ($chunk = $diff['e'][0][1]['data'] ?? false) {
            $content = str_replace('<br/>', PHP_EOL, $chunk);
        }
        if ($cache = $diff['response']['rendered'][2][1][4][4] ?? false) {
            $content = (new HtmlConverter())->convert($cache);
            $stop = true;
        }

        return new self($content, $stop);
    }
}
