<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Http;

interface TransportInterface
{
    /**
     * @param array{headers?: array<string, string>, body?: string|null} $options
     */
    public function send(string $method, string $url, array $options = []): Response;
}
