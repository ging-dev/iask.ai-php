<?php

namespace Gingdev\IAskAI\Internal;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
final class Inspector
{
    /**
     * @return array{string, string, string}
     */
    public static function inspect(
        HttpClientInterface $client,
        string $uri,
    ): array {
        $browser = new HttpBrowser($client);
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
