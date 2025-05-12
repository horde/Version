<?php

declare(strict_types=1);

namespace Horde\Version;

use RuntimeException;

class InvalidVersionException extends RuntimeException
{
    public function __construct(
        string $message = 'Version did not parse to expected format',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
