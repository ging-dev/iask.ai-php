<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Websocket\WebsocketMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Stringable;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * @internal
 */
final class Chunk
{
    public const END = '2.1.4.4';

    private function __construct(
        public Stringable $content,
        public bool $stop,
    ) {
    }

    public static function from(WebsocketMessage $message): self
    {
        $data = json_decode($message->buffer(), true);
        $diff = array_pop($data);
        $content = str('');
        $stop = Arr::has($diff, self::END);
        if ($chunk = data_get($diff, 'e.0.1.data')) {
            $content = $content->append($chunk)->replace('<br/>', PHP_EOL);
        }
        if ($cache = data_get($diff, 'response.rendered.'.self::END)) {
            $content = $content->append($cache)->pipe(
                (new HtmlConverter())->convert(...)
            );
            $stop = true;
        }

        return new self($content, $stop);
    }
}
