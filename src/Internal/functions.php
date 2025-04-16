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
    $csrfToken = $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content');

    return [
        json_encode([
            null,
            null,
            "lv:{$dom->attr('id')}",
            'phx_join',
            [
                'params' => [
                    '_csrf_token' => $csrfToken,
                ],
                'url' => $response->getRequest()->getUri(),
                'session' => $dom->attr('data-phx-session'),
            ],
        ]),
        $csrfToken,
    ];
}

/**
 * @internal
 *
 * @return array{string, bool}
 */
function parseMessage(WebsocketMessage $message): array
{
    static $htmlConverter = null;
    if (null === $htmlConverter) {
        $htmlConverter = new HtmlConverter(['strip_tags' => true]);
    }
    $data = json_decode($message->buffer(), true);
    $diff = array_pop($data);
    $content = '';
    $continue = true;
    if ($chunk = $diff['e'][0][1]['data'] ?? false) {
        $content = $htmlConverter->convert($chunk);
    } else {
        cachedFind($diff, $cache);
        if ($cache) {
            $continue = false;
            if (isset($diff['response'])) {
                $content = $htmlConverter->convert($cache);
            }
        }
    }

    return [$content, $continue];
}

/**
 * @internal
 *
 * @param mixed[] $data
 */
function cachedFind(array $data, ?string &$cache): void
{
    if ($cache) {
        return;
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            cachedFind($value, $cache);
        }
        if (!is_string($value)) {
            continue;
        }
        if (preg_match('#<p>.+?</p>#', $value)) {
            $cache = $value;
        }
    }
}
