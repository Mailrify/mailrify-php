<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Utils;

final class Arr
{
    private function __construct()
    {
    }

    /**
     * @param  array<string, mixed> $values
     * @return array<string, mixed>
     */
    public static function filterNull(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => $value !== null
        );
    }

    /**
     * @param  array<string, mixed> $values
     * @return array<string, mixed>
     */
    public static function flattenQuery(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $value;
                continue;
            }
            if ($value === null) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
