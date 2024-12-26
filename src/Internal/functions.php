<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Http\Client\Response;
use Amp\Websocket\WebsocketMessage;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 *
 * @return array{string, string}
 */
function inspect(Response $response): array
{
    $crawler = new Crawler($response->getBody()->buffer());
    $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-")]');

    return [
        json_encode([
            null,
            null,
            "lv:{$dom->attr('id')}",
            'phx_join',
            [
                'url' => $response->getRequest()->getUri(),
                'session' => $dom->attr('data-phx-session'),
            ],
        ]),
        $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content'),
    ];
}

/**
 * @internal
 *
 * @param mixed[] $value
 */
function dot(string $keys, array $value): string|false
{
    foreach (explode('.', $keys) as $key) {
        if (!array_key_exists($key, $value)) {
            return false;
        }
        $value = $value[$key];
    }

    return $value;
}

/**
 * @internal
 *
 * @return array{string, bool}
 */
function parseMessage(WebsocketMessage $message): array
{
    $suffix = '2.1.4.4';
    $data = json_decode($message->buffer(), true);
    $diff = array_pop($data);
    $content = '';
    $continue = false === dot($suffix, $diff);
    if ($cache = dot("response.rendered.$suffix", $diff)) {
        $content = (new HtmlConverter())->convert($cache);
        $continue = false;
    }
    if ($chunk = dot('e.0.1.data', $diff)) {
        $content = str_replace('<br/>', PHP_EOL, $chunk);
    }

    return [$content, $continue];
}
