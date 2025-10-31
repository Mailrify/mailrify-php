<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Utils;

use JsonException;
use Mailrify\Sdk\Exceptions\MailrifyException;

final class Json
{
    private function __construct()
    {
    }

    public static function encode(mixed $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MailrifyException('Unable to encode payload as JSON.', 0, $exception);
        }
    }

    public static function decode(string $payload): mixed
    {
        if ($payload === '') {
            return null;
        }

        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new MailrifyException('Unable to decode JSON response from Mailrify.', 0, $exception);
        }
    }
}
