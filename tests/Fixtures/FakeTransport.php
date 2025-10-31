<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Fixtures;

use Mailrify\Sdk\Http\Response;
use Mailrify\Sdk\Http\TransportInterface;
use RuntimeException;
use Throwable;

final class FakeTransport implements TransportInterface
{
    /** @var array<int, Response> */
    private array $responses;

    public ?Throwable $exception = null;

    /** @var list<array{method: string, url: string, options: array<string, mixed>}> */
    public array $calls = [];

    public function __construct(Response ...$responses)
    {
        $this->responses = array_values($responses);
    }

    public function queue(Response $response): void
    {
        $this->responses[] = $response;
    }

    /** @param array{headers?: array<string, string>, body?: string|null} $options */
    public function send(string $method, string $url, array $options = []): Response
    {
        $this->calls[] = [
            'method' => $method,
            'url' => $url,
            'options' => $options,
        ];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->responses === []) {
            throw new RuntimeException('No fake response configured.');
        }

        $response = array_shift($this->responses);
        if (!$response instanceof Response) {
            throw new RuntimeException('No fake response configured.');
        }

        return $response;
    }
}
