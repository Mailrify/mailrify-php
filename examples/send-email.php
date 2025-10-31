<?php

declare(strict_types=1);

use Mailrify\Sdk\Client;

autoload();

$client = Client::create([
    'apiKey' => getenv('MAILRIFY_API_KEY') ?: '',
]);

$response = $client->emails()->send([
    'from' => 'you@example.com',
    'to' => 'customer@example.com',
    'subject' => 'Welcome to Mailrify',
    'text' => 'This is a sample integration test email.',
]);

echo 'Queued email ID: ' . $response->emailId . PHP_EOL;

function autoload(): void
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        fwrite(STDERR, "Run 'composer install' before executing examples." . PHP_EOL);
        exit(1);
    }

    require $autoload;
}
