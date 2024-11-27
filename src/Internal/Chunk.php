<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Websocket\WebsocketMessage;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * @internal
 */
final class Chunk
{
    private const END = '2.1.4.4';

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
        $stop = false !== self::dot(self::END, $diff);
        if ($chunk = self::dot('e.0.1.data', $diff)) {
            $content = str_replace('<br/>', PHP_EOL, $chunk);
        }
        if ($cache = self::dot('response.rendered.'.self::END, $diff)) {
            $content = (new HtmlConverter())->convert($cache);
            $stop = true;
        }

        return new self($content, $stop);
    }

    /**
     * @param mixed[] $value
     */
    private static function dot(string $keys, array $value): string|false
    {
        foreach (explode('.', $keys) as $key) {
            if (!array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
