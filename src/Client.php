<?php

namespace Gingdev\IAskAI;

use Gingdev\IAskAI\Internal\Askable;
use Gingdev\IAskAI\Internal\IResponse;
use Hyperf\Collection\Arr;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Wrench\Client as WebSocketClient;

use function Hyperf\Collection\collect;
use function Hyperf\Collection\data_get;
use function Hyperf\Stringable\str;

class Client implements Askable, IResponse
{
    public const BASE_URL = 'https://iask.ai';
    public const WEBSOCKET_URL = 'wss://iask.ai/live/websocket';
    public const ASKAI_KEY = '_askai_key';

    private string $channel;
    private string $session;

    private WebSocketClient $wsClient;

    public function __construct(?HttpClientInterface $client = null)
    {
        $browser = new HttpBrowser($client ?: HttpClient::create());
        $crawler = $browser->request('GET', self::BASE_URL);
        $token = $crawler->filterXPath('//meta[@name="csrf-token"]')->attr('content');
        $session = $crawler->filterXPath('//div[starts-with(@id, "phx-F_")]');
        $wsClient = new WebSocketClient(self::WEBSOCKET_URL."?_csrf_token={$token}&vsn=2.0.0", self::BASE_URL);
        $wsClient->addRequestHeader(
            'cookie',
            sprintf(
                '%s=%s',
                self::ASKAI_KEY,
                $browser->getCookieJar()->get(self::ASKAI_KEY)->getRawValue()
            )
        );
        if (!$wsClient->connect()) {
            throw new \RuntimeException('Disconnect from websocket.');
        }
        $this->channel = 'lv:'.$session->attr('id');
        $this->session = $session->attr('data-phx-session');
        $this->wsClient = $wsClient;
    }

    public function ask(
        string $input,
        string $mode = 'question',
        string $level = 'detailed'
    ): IResponse {
        $data = collect([
            null,
            null,
            $this->channel,
            'phx_join',
            [
                'url' => self::BASE_URL.'?'.http_build_query([
                    'q' => $input,
                    'mode' => $mode,
                    'options' => [
                        'detail_level' => $level,
                    ],
                ]),
                'session' => $this->session,
            ],
        ])->toJson();
        $this->wsClient->sendData($data);

        return $this;
    }

    public function stream(): iterable
    {
        static $emptyChunkCount = 0;
        foreach ($this->wsClient->receive() as $json) {
            $reply = json_decode($json, true);
            if (Arr::has($reply, $key = '4.response.rendered.1.1.4.3')) {
                $anwser = data_get($reply, $key);
                if ('' === $anwser) {
                    continue;
                }
                yield (new HtmlConverter())->convert($anwser);

                return;
            }
            if (!Arr::has($reply, $key = '4.e.0.1.data')) {
                continue;
            }
            $chunk = str(data_get($reply, $key))
                ->replace('<br/>', PHP_EOL)
                ->toString();
            if ('' === $chunk) {
                ++$emptyChunkCount;
                continue;
            }
            yield $chunk;
        }
        if ($this->wsClient->isConnected() && $emptyChunkCount < 2) {
            yield from $this->stream();
        }
    }

    public function get(): string
    {
        $result = '';
        foreach ($this->stream() as $chunk) {
            $result .= $chunk;
        }

        return $result;
    }

    public function __destruct()
    {
        $this->wsClient->disconnect();
    }
}
