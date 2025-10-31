<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models\Enums;

enum DomainStatus: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case TEMPORARY_FAILURE = 'TEMPORARY_FAILURE';
}
