<?php

namespace Gingdev\IAskAI\Listeners;

use Amp\Websocket\Client\WebsocketHandshake;
use Gingdev\IAskAI\Events\JoinEvent;
use Illuminate\Support\Arr;
use League\HTMLToMarkdown\HtmlConverter;
use League\Uri\Uri;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Amp\async;
use function Amp\Websocket\Client\connect;

class JoinListener
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    private HttpBrowser $browser;

    public function __construct(HttpClientInterface $client)
    {
        $this->browser = new HttpBrowser($client);
    }

    public function onJoin(JoinEvent $event): void
    {
        $uri = Uri::new(static::BASE_URL)->withQuery($event->getQuery());
        $crawler = $this->browser->request('GET', $uri);
        $csrftoken = $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content');
        $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-F_")]');
        $handshake = (new WebsocketHandshake(static::WEBSOCKET_URL))
            ->withQueryParameter('_csrf_token', $csrftoken)
            ->withQueryParameter('vsn', '2.0.0')
            ->withHeader('Cookie', implode('; ', $this->getCookies($uri)))
        ;
        $message = sprintf(
            '[null,null,"lv:%s","phx_join",%s]',
            $dom->attr('id'),
            json_encode([
                'url' => $uri,
                'session' => $dom->attr('data-phx-session'),
            ])
        );
        $queue = $event->getQueue();
        async(static function () use ($queue, $handshake, $message): void {
            $client = connect($handshake);
            $client->sendText($message);
            $endSuffix = '3.1.4.3';
            while ($message = $client->receive()) {
                $data = json_decode($message->buffer(), true);
                $diff = array_pop($data);
                if ($chunk = data_get($diff, 'e.0.1.data')) {
                    $queue->push(str($chunk)->replace('<br/>', PHP_EOL)->toString());
                }
                if (
                    ($cache = data_get($diff, "response.rendered.{$endSuffix}"))
                    || Arr::has($diff, $endSuffix)
                ) {
                    if ($cache) {
                        $queue->push((new HtmlConverter())->convert($cache));
                    }
                    $queue->complete();
                    $client->close();

                    return;
                }
            }
        });
    }

    /**
     * @return string[]
     */
    private function getCookies(string $uri): array
    {
        $cookies = [];
        foreach ($this->browser->getCookieJar()->allRawValues($uri) as $key => $value) {
            $cookies[] = $key.'='.$value;
        }

        return $cookies;
    }
}
