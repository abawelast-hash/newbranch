<?php

namespace App\Exceptions\Formula;

use RuntimeException;

/**
 * Thrown when a division-by-zero occurs during expression evaluation.
 */
class DivisionByZeroException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('لا يمكن القسمة على صفر في الصيغة الحسابية.');
    }
}
