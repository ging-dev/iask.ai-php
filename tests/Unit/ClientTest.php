<?php
use Gingdev\IAskAI\Client;
use function Amp\ByteStream\buffer;

test('responses', function () {
    $client = Client::create();

    expect(buffer($client->ask('Who is Goku?')))->toBeString();
    expect(buffer($client->ask([
        'q' => 'Who is Linus?',
        'mode' => 'forums',
        'options' => [
            'detail_level' => 'comprehensive',
        ]
    ])))->toBeString();
});
