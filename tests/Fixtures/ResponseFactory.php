<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Tests\Fixtures;

use Mailrify\Sdk\Http\Response;

final class ResponseFactory
{
    private function __construct()
    {
    }

    /**
     * @param array<int|string, mixed> $data
     * @param array<string, string>    $headers
     */
    public static function json(int $statusCode, array $data, array $headers = []): Response
    {
        $headers = array_merge(['content-type' => 'application/json'], $headers);

        return new Response($statusCode, $headers, json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, string> $headers
     */
    public static function raw(int $statusCode, string $body = '', array $headers = []): Response
    {
        return new Response($statusCode, $headers, $body);
    }
}
