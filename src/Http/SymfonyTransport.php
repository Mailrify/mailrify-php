<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Http;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SymfonyTransport implements TransportInterface
{
    public function __construct(private readonly HttpClientInterface $client)
    {
    }

    /** @param array{headers?: array<string, string>, body?: string|null} $options */
    public function send(string $method, string $url, array $options = []): Response
    {
        $requestOptions = [
            'headers' => $options['headers'] ?? [],
        ];

        if (array_key_exists('body', $options) && $options['body'] !== null) {
            $requestOptions['body'] = $options['body'];
        }

        try {
            $response = $this->client->request($method, $url, $requestOptions);
            $headers = $response->getHeaders(false);
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw $exception;
        }

        return new Response(
            $response->getStatusCode(),
            $headers,
            $content
        );
    }
}
