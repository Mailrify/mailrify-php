<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr18Transport implements TransportInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

    /** @param array{headers?: array<string, string>, body?: string|null} $options */
    public function send(string $method, string $url, array $options = []): Response
    {
        $request = $this->requestFactory->createRequest($method, $url);

        foreach ($options['headers'] ?? [] as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        $body = $options['body'] ?? null;
        if ($body !== null) {
            $stream = $this->streamFactory->createStream($body);
            $request = $request->withBody($stream);
        }

        $psrResponse = $this->client->sendRequest($request);

        $headers = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[$name] = array_values($values);
        }

        return new Response(
            $psrResponse->getStatusCode(),
            $headers,
            (string) $psrResponse->getBody()
        );
    }
}
