<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Exceptions;

use Throwable;

final class NetworkException extends MailrifyException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
