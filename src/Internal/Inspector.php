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
        private string $csrfToken,
    ) {
    }

    public static function inspect(Response $response): self
    {
        $crawler = new Crawler($response->getBody()->buffer());
        $dom = $crawler->filterXPath('//*[starts-with(@id, "phx-")]');

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
        return $this->csrfToken;
    }

    public function getJoinMessage(): string
    {
        return $this->joinMessage;
    }
}
