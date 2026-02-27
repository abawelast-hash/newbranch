<?php

namespace App\Services;

use App\Exceptions\Formula\DivisionByZeroException;
use App\Exceptions\Formula\ExpressionTooLongException;
use App\Exceptions\Formula\InvalidExpressionException;
use App\Exceptions\Formula\UnbalancedParenthesesException;

/**
 * SARH v4.0 — محرك الصيغ الآمن (بدون eval)
 *
 * يستخدم خوارزمية Shunting-yard لتحويل الصيغة إلى Reverse Polish Notation
 * ثم يقيّمها بشكل آمن تماماً دون استخدام eval() أو أي تنفيذ كود ديناميكي.
 *
 * المتغيرات المدعومة: أي أسماء أحرف (a-z, A-Z, _) يتم استبدالها ببياناتها الفعلية.
 * المعاملات المدعومة: + - * / % ( )
 * الحد الأقصى لطول الصيغة: 500 حرف
 * الحد الأقصى لعمق التداخل: 20 قوس
 *
 * مثال الاستخدام:
 *   $engine = new SafeFormulaEngine();
 *   $score = $engine->evaluate('(attendance * 0.4) + (task_completion * 0.6)', [
 *       'attendance'       => 95.0,
 *       'task_completion'  => 80.0,
 *   ]);
 *   // => 86.0
 */
class SafeFormulaEngine
{
    /** @var int Maximum allowed expression length in characters */
    protected int $maxLength = 500;

    /** @var int Maximum allowed parenthesis nesting depth */
    protected int $maxNesting = 20;

    /**
     * Operator precedence table.
     * Higher value = higher precedence.
     */
    private const PRECEDENCE = [
        '+' => 1,
        '-' => 1,
        '*' => 2,
        '/' => 2,
        '%' => 2,
    ];

    /*
    |--------------------------------------------------------------------------
    | PUBLIC API
    |--------------------------------------------------------------------------
    */

    /**
     * Evaluate an arithmetic expression with optional variable substitution.
     *
     * @param  string              $expression  Raw formula string, e.g. "(attendance * 0.4) + 10"
     * @param  array<string,float> $data        Variable values, e.g. ['attendance' => 95.5]
     * @return float               Computed numeric result
     *
     * @throws ExpressionTooLongException
     * @throws InvalidExpressionException
     * @throws UnbalancedParenthesesException
     * @throws DivisionByZeroException
     */
    public function evaluate(string $expression, array $data = []): float
    {
        // 1. Check length
        if (mb_strlen($expression) > $this->maxLength) {
            throw new ExpressionTooLongException($this->maxLength);
        }

        // 2. Substitute variables (longest first to avoid partial replacements)
        $expression = $this->substituteVariables($expression, $data);

        // 3. Sanitize & validate characters
        $expression = $this->sanitize($expression);

        // 4. Check for empty
        if (trim($expression) === '') {
            throw InvalidExpressionException::empty();
        }

        // 5. Check parentheses balance
        $this->checkParentheses($expression);

        // 6. Tokenize
        $tokens = $this->tokenize($expression);

        // 7. Convert to RPN via Shunting-yard
        $rpn = $this->toRPN($tokens);

        // 8. Evaluate RPN
        return $this->evaluateRPN($rpn);
    }

    /**
     * Validate a formula syntactically without evaluating.
     * Uses placeholder value 1.0 for all variables.
     *
     * @param  string              $expression
     * @param  array<string,mixed> $variables   Variable definitions (keys only needed)
     * @return bool
     */
    public function validate(string $expression, array $variables = []): bool
    {
        $data = array_fill_keys(array_keys($variables), 1.0);
        try {
            $this->evaluate($expression, $data);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 1 — VARIABLE SUBSTITUTION
    |--------------------------------------------------------------------------
    */

    /**
     * Replace named variables with their numeric string values.
     * Processes longest names first to avoid partial replacement.
     *
     * @param  string              $expression
     * @param  array<string,float> $data
     * @return string
     */
    protected function substituteVariables(string $expression, array $data): string
    {
        if (empty($data)) {
            return $expression;
        }

        // Sort by name length descending to avoid partial replacements
        uksort($data, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($data as $name => $value) {
            // Only replace whole-word occurrences (not substrings inside other names)
            $expression = preg_replace(
                '/\b' . preg_quote($name, '/') . '\b/',
                (string) (float) $value,
                $expression
            );
        }

        return $expression;
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2 — SANITIZE & VALIDATE CHARACTERS
    |--------------------------------------------------------------------------
    */

    /**
     * Strip whitespace normalisation and reject disallowed characters/patterns.
     * After variable substitution, only numbers, operators, parens, dots, spaces
     * should remain. Any remaining letter sequences indicate un-substituted variables
     * or injection attempts.
     *
     * @throws InvalidExpressionException
     */
    protected function sanitize(string $expression): string
    {
        // Block obvious injection patterns before any other processing
        $dangerousPatterns = [
            '/\b(system|exec|passthru|shell_exec|popen|proc_open|eval|assert|include|require|file_get_contents|phpinfo|base64_decode|chr|ord|hex2bin)\b/i',
            '/\$[a-zA-Z_]/',  // PHP variables
            '/`/',             // Backtick execution
            '/\?>/',           // PHP closing tag
            '/<\?/',           // PHP opening tag
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                throw InvalidExpressionException::maliciousPattern($expression);
            }
        }

        // After variable substitution, only these characters should remain:
        // digits, decimal points, operators (+, -, *, /, %), parentheses, spaces
        $illegal = preg_replace('/[0-9\s\+\-\*\/\%\(\)\.]/', '', $expression);
        if ($illegal !== '') {
            throw InvalidExpressionException::illegalCharacters($illegal);
        }

        // Normalize whitespace
        return preg_replace('/\s+/', ' ', trim($expression));
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3 — PARENTHESES BALANCE CHECK
    |--------------------------------------------------------------------------
    */

    /**
     * Verify parentheses are balanced and nesting depth is within limit.
     *
     * @throws UnbalancedParenthesesException
     * @throws InvalidExpressionException
     */
    protected function checkParentheses(string $expression): void
    {
        $depth  = 0;
        $length = strlen($expression);

        for ($i = 0; $i < $length; $i++) {
            if ($expression[$i] === '(') {
                $depth++;
                if ($depth > $this->maxNesting) {
                    throw new InvalidExpressionException(
                        "عمق التداخل في الأقواس يتجاوز الحد المسموح ({$this->maxNesting})."
                    );
                }
            } elseif ($expression[$i] === ')') {
                $depth--;
                if ($depth < 0) {
                    throw new UnbalancedParenthesesException();
                }
            }
        }

        if ($depth !== 0) {
            throw new UnbalancedParenthesesException();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 4 — TOKENIZER
    |--------------------------------------------------------------------------
    */

    /**
     * Split the sanitized expression string into typed tokens.
     *
     * Token types: 'number' | 'operator' | 'lparen' | 'rparen'
     *
     * @return array<int, array{type: string, value: string}>
     */
    protected function tokenize(string $expression): array
    {
        $tokens  = [];
        $length  = strlen($expression);
        $i       = 0;
        $prevType = null; // used to detect unary minus

        while ($i < $length) {
            $ch = $expression[$i];

            // Skip spaces
            if ($ch === ' ') {
                $i++;
                continue;
            }

            // Number (integer or decimal)
            if (ctype_digit($ch) || $ch === '.') {
                $num = '';
                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $num .= $expression[$i];
                    $i++;
                }
                // Reject multiple decimal points
                if (substr_count($num, '.') > 1) {
                    throw InvalidExpressionException::illegalCharacters($num);
                }
                $tokens[]  = ['type' => 'number', 'value' => $num];
                $prevType  = 'number';
                continue;
            }

            // Unary minus: treat as operator with value 'u-'
            // A minus is unary when: first token, or follows '(' or another operator
            if ($ch === '-' && ($prevType === null || $prevType === 'operator' || $prevType === 'lparen')) {
                $tokens[]  = ['type' => 'operator', 'value' => 'u-'];
                $prevType  = 'operator';
                $i++;
                continue;
            }

            // Binary operators
            if (in_array($ch, ['+', '-', '*', '/', '%'], true)) {
                $tokens[]  = ['type' => 'operator', 'value' => $ch];
                $prevType  = 'operator';
                $i++;
                continue;
            }

            // Left parenthesis
            if ($ch === '(') {
                $tokens[]  = ['type' => 'lparen', 'value' => '('];
                $prevType  = 'lparen';
                $i++;
                continue;
            }

            // Right parenthesis
            if ($ch === ')') {
                $tokens[]  = ['type' => 'rparen', 'value' => ')'];
                $prevType  = 'rparen';
                $i++;
                continue;
            }

            throw InvalidExpressionException::illegalCharacters($ch);
        }

        return $tokens;
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 5 — SHUNTING-YARD → RPN
    |--------------------------------------------------------------------------
    */

    /**
     * Convert infix token list to Reverse Polish Notation using Shunting-yard.
     *
     * @param  array<int, array{type: string, value: string}> $tokens
     * @return array<int, array{type: string, value: string}>
     */
    protected function toRPN(array $tokens): array
    {
        $output    = [];
        $opStack   = [];
        $precedence = self::PRECEDENCE + ['u-' => 3]; // unary minus is highest

        foreach ($tokens as $token) {
            switch ($token['type']) {
                case 'number':
                    $output[] = $token;
                    break;

                case 'operator':
                    $op = $token['value'];

                    // While top of stack has higher/equal precedence operator, pop to output
                    while (
                        !empty($opStack) &&
                        end($opStack)['type'] === 'operator' &&
                        isset($precedence[end($opStack)['value']]) &&
                        $precedence[end($opStack)['value']] >= $precedence[$op] &&
                        $op !== 'u-' // unary minus is right-associative
                    ) {
                        $output[] = array_pop($opStack);
                    }
                    $opStack[] = $token;
                    break;

                case 'lparen':
                    $opStack[] = $token;
                    break;

                case 'rparen':
                    // Pop until matching left paren
                    $foundLeft = false;
                    while (!empty($opStack)) {
                        $top = array_pop($opStack);
                        if ($top['type'] === 'lparen') {
                            $foundLeft = true;
                            break;
                        }
                        $output[] = $top;
                    }
                    if (!$foundLeft) {
                        throw new UnbalancedParenthesesException();
                    }
                    break;
            }
        }

        // Drain remaining operators
        while (!empty($opStack)) {
            $top = array_pop($opStack);
            if ($top['type'] === 'lparen' || $top['type'] === 'rparen') {
                throw new UnbalancedParenthesesException();
            }
            $output[] = $top;
        }

        return $output;
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 6 — RPN EVALUATION
    |--------------------------------------------------------------------------
    */

    /**
     * Evaluate an RPN token list using a numeric stack.
     *
     * @param  array<int, array{type: string, value: string}> $rpn
     * @throws DivisionByZeroException
     */
    protected function evaluateRPN(array $rpn): float
    {
        $stack = [];

        foreach ($rpn as $token) {
            if ($token['type'] === 'number') {
                $stack[] = (float) $token['value'];
                continue;
            }

            // Unary minus
            if ($token['value'] === 'u-') {
                if (empty($stack)) {
                    throw InvalidExpressionException::empty();
                }
                $stack[] = -(array_pop($stack));
                continue;
            }

            // Binary operator — needs two operands
            if (count($stack) < 2) {
                throw new InvalidExpressionException('صيغة غير صالحة: عدد غير كافٍ من المعاملات.');
            }

            $b = array_pop($stack);
            $a = array_pop($stack);

            $stack[] = match ($token['value']) {
                '+'     => $a + $b,
                '-'     => $a - $b,
                '*'     => $a * $b,
                '%'     => $a % $b,
                '/'     => $b == 0.0 ? throw new DivisionByZeroException() : $a / $b,
                default => throw new InvalidExpressionException("معامل غير معروف: {$token['value']}"),
            };
        }

        if (count($stack) !== 1) {
            throw new InvalidExpressionException('صيغة غير صالحة: نتيجة غير محددة.');
        }

        return round((float) $stack[0], 10); // preserve precision; callers can round further
    }
}
