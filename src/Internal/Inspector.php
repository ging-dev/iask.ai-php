<?php

namespace Gingdev\IAskAI\Internal;

use Amp\Http\Client\Response;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
final class Inspector
{
    private function __construct(
        private string $joinMessage,
        private string $csrftoken,
    ) {
    }

    public static function inspect(Response $response): self
    {
        $crawler = new Crawler($response->getBody()->buffer());
        $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-F_")]');

        return new self(
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
            $crawler->filterXPath('//*[@name="csrf-token"]')->attr('content')
        );
    }

    public function getCsrfToken(): string
    {
        return $this->csrftoken;
    }

    public function getJoinMessage(): string
    {
        return $this->joinMessage;
    }
}
