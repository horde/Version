<?php

declare(strict_types=1);

namespace Horde\Version;

use RuntimeException;
use Throwable;

/**
 * Thrown when a constraint cannot be expressed as a single contiguous
 * version interval.
 *
 * Applies to OR composites (a union of intervals) and bare inequality
 * constraints (!=, <>)
 *
 * @copyright 2013-2026 The Horde Project (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 */
class NotIntervalableException extends RuntimeException
{
    public function __construct(
        string $message = 'Constraint cannot be expressed as a single version interval',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
