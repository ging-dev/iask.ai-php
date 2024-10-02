<?php

namespace Gingdev\IAskAI\Internal;

use Symfony\Component\BrowserKit\HttpBrowser;

/**
 * @internal
 */
final class Inspector
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    /**
     * @return array{string, string, string}
     */
    public static function inspect(string $uri): array
    {
        $browser = new HttpBrowser();
        $crawler = $browser->request('GET', $uri);
        $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-F_")]');
        $csrftoken = $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content');
        $cookies = [];
        foreach ($browser->getCookieJar()->allRawValues($uri) as $key => $value) {
            $cookies[] = $key.'='.$value;
        }
        $message = sprintf(
            '[null,null,"lv:%s","phx_join",%s]',
            $dom->attr('id'),
            json_encode([
                'url' => $uri,
                'session' => $dom->attr('data-phx-session'),
            ])
        );

        return [implode('; ', $cookies), $csrftoken, $message];
    }
}
