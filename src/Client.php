<?php

namespace Gingdev\IAskAI;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableStream;
use Amp\Pipeline\Queue;
use Amp\Websocket\Client\WebsocketHandshake;
use Gingdev\IAskAI\Contracts\AskableInterface;
use Hyperf\Collection\Arr;
use League\HTMLToMarkdown\HtmlConverter;
use League\Uri\Uri;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Amp\async;
use function Amp\Websocket\Client\connect;
use function Hyperf\Collection\collect;
use function Hyperf\Collection\data_get;
use function Hyperf\Stringable\str;

final class Client implements AskableInterface
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';

    private function __construct(private HttpBrowser $browser)
    {
    }

    public static function create(?HttpClientInterface $client = null): AskableInterface
    {
        return new self(new HttpBrowser($client ?: HttpClient::create()));
    }

    public function ask(array|string $input): ReadableStream
    {
        $query = http_build_query(is_array($input) ? $input : ['q' => $input]);

        return $this->createReadableForUri(Uri::new(self::BASE_URL)->withQuery($query));
    }

    private function createReadableForUri(string $uri): ReadableIterableStream
    {
        $crawler = $this->browser->request('GET', $uri);
        $csrftoken = $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content');
        $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-F_")]');
        $handshake = (new WebsocketHandshake(self::WEBSOCKET_URL))
            ->withQueryParameter('_csrf_token', $csrftoken)
            ->withQueryParameter('vsn', '2.0.0')
            ->withHeader('Cookie', implode('; ', $this->getCookies($uri)))
        ;
        $data = collect([null, null, 'lv:'.$dom->attr('id'), 'phx_join', [
            'url' => $uri,
            'session' => $dom->attr('data-phx-session'),
        ]])->toJson();

        $client = connect($handshake);
        $client->sendText($data);

        /** @var Queue<string> */
        $queue = new Queue();
        async(static function () use ($client, $queue): void {
            $endSuffix = '1.1.4.3';
            while ($message = $client->receive()) {
                $data = json_decode($message->buffer(), true);
                $diff = array_pop($data);
                if (Arr::has($diff, $endSuffix)) {
                    return;
                }
                if ($cache = data_get($diff, "response.rendered.{$endSuffix}")) {
                    $queue->push((new HtmlConverter())->convert($cache));

                    return;
                }
                if ($chunk = data_get($diff, 'e.0.1.data')) {
                    $queue->push(str($chunk)->replace('<br/>', PHP_EOL)->toString());
                }
            }
        })->finally(function () use ($client, $queue): void {
            $queue->complete();
            $client->close();
        });

        return new ReadableIterableStream($queue->pipe());
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
