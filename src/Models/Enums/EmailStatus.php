<?php

declare(strict_types=1);

namespace Mailrify\Sdk\Models\Enums;

enum EmailStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case QUEUED = 'QUEUED';
    case SENT = 'SENT';
    case DELIVERY_DELAYED = 'DELIVERY_DELAYED';
    case BOUNCED = 'BOUNCED';
    case REJECTED = 'REJECTED';
    case RENDERING_FAILURE = 'RENDERING_FAILURE';
    case DELIVERED = 'DELIVERED';
    case OPENED = 'OPENED';
    case CLICKED = 'CLICKED';
    case COMPLAINED = 'COMPLAINED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case SUPPRESSED = 'SUPPRESSED';
}
