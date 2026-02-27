<?php

namespace App\Exceptions\Formula;

use RuntimeException;

/**
 * Thrown when a formula expression exceeds the allowed length.
 */
class ExpressionTooLongException extends RuntimeException
{
    public function __construct(int $max)
    {
        parent::__construct("الصيغة تتجاوز الحد الأقصى للطول ({$max} حرف).");
    }
}
