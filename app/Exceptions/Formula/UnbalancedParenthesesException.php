<?php

namespace App\Exceptions\Formula;

use RuntimeException;

/**
 * Thrown when parentheses in a formula are unbalanced.
 */
class UnbalancedParenthesesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('الأقواس في الصيغة غير متوازنة.');
    }
}
