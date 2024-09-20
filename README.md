## iAskAI Unofficial PHP Client
The iAskAI PHP client is a simple and efficient way to interact with the IAskAI API, allowing developers to easily integrate AI-driven responses into their PHP applications.

## Installation
```bash
composer require gingdev/iaskai
```

## Usage
```php
<?php

use Gingdev\IAskAI\Client;

use function Amp\ByteStream\buffer;

require __DIR__.'/vendor/autoload.php';

$client = Client::create();

$stream1 = $client->ask('Who is Goku?');
$stream2 = $client->ask('Who is Light Yagami?');

echo buffer($stream1);

// streaming response
foreach ($stream2 as $chunk) {
    echo $chunk;
}
```

## Features
- **Asynchronous Support:** The client is designed to work with asynchronous programming, making it suitable for applications that require non-blocking I/O operations.
- **Streaming Responses:** The ability to handle streaming responses allows for real-time data processing and display, enhancing user experience.
- **Easy Integration:** The client is easy to integrate into existing PHP applications, requiring minimal setup.
