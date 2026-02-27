<?php

namespace App\Exceptions\Formula;

use InvalidArgumentException;

/**
 * Thrown when a formula expression contains invalid or disallowed syntax.
 */
class InvalidExpressionException extends InvalidArgumentException
{
    public static function illegalCharacters(string $illegal): self
    {
        return new self("الصيغة تحتوي على رموز غير مسموحة: [{$illegal}]");
    }

    public static function maliciousPattern(string $pattern): self
    {
        return new self("الصيغة تحتوي على نمط خطير: [{$pattern}]");
    }

    public static function empty(): self
    {
        return new self('لا يمكن تقييم صيغة فارغة.');
    }
}
