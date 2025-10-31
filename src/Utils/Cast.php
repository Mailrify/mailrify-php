<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Utils;

final class Cast
{
    private function __construct()
    {
    }

    public static function string(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    public static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function bool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        return $default;
    }

    public static function nullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return self::int($value);
    }

    /**
     * @param  mixed        $value
     * @return list<string>
     */
    public static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $entry) {
            if (is_string($entry)) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * @param  mixed                 $value
     * @return array<string, string>
     */
    public static function stringMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $entry) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($entry) || is_int($entry) || is_float($entry)) {
                $result[$key] = (string) $entry;
            }
        }

        return $result;
    }
}
