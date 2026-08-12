<?php

namespace App\Exceptions;

use RuntimeException;

class GraphRateLimitedException extends RuntimeException
{
    public function __construct(public int $retryAfterSeconds)
    {
        parent::__construct("Microsoft Graph rate limited. Retry after {$retryAfterSeconds}s.");
    }
}
