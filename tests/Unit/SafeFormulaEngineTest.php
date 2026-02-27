<?php

namespace Tests\Unit;

use App\Exceptions\Formula\DivisionByZeroException;
use App\Exceptions\Formula\ExpressionTooLongException;
use App\Exceptions\Formula\InvalidExpressionException;
use App\Exceptions\Formula\UnbalancedParenthesesException;
use App\Services\SafeFormulaEngine;
use Tests\TestCase;

/**
 * Unit tests for SafeFormulaEngine.
 *
 * Covers: basic arithmetic, operator precedence, parentheses, unary minus,
 * variable substitution, security injection, error conditions.
 */
class SafeFormulaEngineTest extends TestCase
{
    protected SafeFormulaEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SafeFormulaEngine();
    }

    // -------------------------------------------------------------------------
    // Basic Arithmetic
    // -------------------------------------------------------------------------

    /** @test */
    public function it_evaluates_simple_addition(): void
    {
        $this->assertEqualsWithDelta(10.0, $this->engine->evaluate('5 + 5'), 0.0001);
    }

    /** @test */
    public function it_evaluates_simple_subtraction(): void
    {
        $this->assertEqualsWithDelta(3.0, $this->engine->evaluate('10 - 7'), 0.0001);
    }

    /** @test */
    public function it_evaluates_multiplication(): void
    {
        $this->assertEqualsWithDelta(15.0, $this->engine->evaluate('3 * 5'), 0.0001);
    }

    /** @test */
    public function it_evaluates_division(): void
    {
        $this->assertEqualsWithDelta(2.5, $this->engine->evaluate('10 / 4'), 0.0001);
    }

    /** @test */
    public function it_evaluates_modulo(): void
    {
        $this->assertEqualsWithDelta(1.0, $this->engine->evaluate('10 % 3'), 0.0001);
    }

    // -------------------------------------------------------------------------
    // Operator Precedence & Parentheses
    // -------------------------------------------------------------------------

    /** @test */
    public function it_respects_operator_precedence(): void
    {
        // 2 + 3 * 4 = 2 + 12 = 14
        $this->assertEqualsWithDelta(14.0, $this->engine->evaluate('2 + 3 * 4'), 0.0001);
    }

    /** @test */
    public function it_respects_explicit_parentheses(): void
    {
        // (2 + 3) * 4 = 5 * 4 = 20
        $this->assertEqualsWithDelta(20.0, $this->engine->evaluate('(2 + 3) * 4'), 0.0001);
    }

    /** @test */
    public function it_handles_nested_parentheses(): void
    {
        // ((2 + 3) * (4 - 1)) = 5 * 3 = 15
        $this->assertEqualsWithDelta(15.0, $this->engine->evaluate('((2 + 3) * (4 - 1))'), 0.0001);
    }

    /** @test */
    public function it_handles_decimal_numbers(): void
    {
        $this->assertEqualsWithDelta(86.0, $this->engine->evaluate('(95 * 0.4) + (80 * 0.6)'), 0.0001);
    }

    // -------------------------------------------------------------------------
    // Unary Minus
    // -------------------------------------------------------------------------

    /** @test */
    public function it_handles_unary_minus(): void
    {
        $this->assertEqualsWithDelta(-5.0, $this->engine->evaluate('-5'), 0.0001);
    }

    /** @test */
    public function it_handles_unary_minus_in_expression(): void
    {
        // 10 + (-3) = 7
        $this->assertEqualsWithDelta(7.0, $this->engine->evaluate('10 + (-3)'), 0.0001);
    }

    // -------------------------------------------------------------------------
    // Variable Substitution
    // -------------------------------------------------------------------------

    /** @test */
    public function it_substitutes_single_variable(): void
    {
        $result = $this->engine->evaluate('cost * 2', ['cost' => 50.0]);
        $this->assertEqualsWithDelta(100.0, $result, 0.0001);
    }

    /** @test */
    public function it_substitutes_multiple_variables(): void
    {
        $result = $this->engine->evaluate(
            '(attendance * 0.4) + (task_completion * 0.6)',
            ['attendance' => 100.0, 'task_completion' => 80.0]
        );
        $this->assertEqualsWithDelta(88.0, $result, 0.0001);
    }

    /** @test */
    public function it_handles_longest_variable_name_first(): void
    {
        // 'on_time_rate' must not be partially replaced by 'on_time' or 'rate'
        $result = $this->engine->evaluate(
            'on_time_rate + 5',
            ['on_time_rate' => 10.0]
        );
        $this->assertEqualsWithDelta(15.0, $result, 0.0001);
    }

    // -------------------------------------------------------------------------
    // Security — Injection Attempts Must Be Rejected
    // -------------------------------------------------------------------------

    /** @test */
    public function it_rejects_system_call_injection(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('5 + system("rm -rf /")');
    }

    /** @test */
    public function it_rejects_eval_injection(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('eval("phpinfo()")');
    }

    /** @test */
    public function it_rejects_php_variable_injection(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('$_SERVER["HTTP_HOST"]');
    }

    /** @test */
    public function it_rejects_backtick_execution(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('`whoami`');
    }

    /** @test */
    public function it_rejects_php_opening_tag(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('<?php echo 1; ?>');
    }

    // -------------------------------------------------------------------------
    // Error Conditions
    // -------------------------------------------------------------------------

    /** @test */
    public function it_throws_on_division_by_zero(): void
    {
        $this->expectException(DivisionByZeroException::class);
        $this->engine->evaluate('10 / 0');
    }

    /** @test */
    public function it_throws_on_expression_too_long(): void
    {
        $longExpr = str_repeat('1+', 260) . '1'; // ~521 chars
        $this->expectException(ExpressionTooLongException::class);
        $this->engine->evaluate($longExpr);
    }

    /** @test */
    public function it_throws_on_unbalanced_open_parenthesis(): void
    {
        $this->expectException(UnbalancedParenthesesException::class);
        $this->engine->evaluate('(2 + 3');
    }

    /** @test */
    public function it_throws_on_unbalanced_close_parenthesis(): void
    {
        $this->expectException(UnbalancedParenthesesException::class);
        $this->engine->evaluate('2 + 3)');
    }

    /** @test */
    public function it_throws_on_empty_expression(): void
    {
        $this->expectException(InvalidExpressionException::class);
        $this->engine->evaluate('');
    }

    // -------------------------------------------------------------------------
    // Validate Helper
    // -------------------------------------------------------------------------

    /** @test */
    public function validate_returns_true_for_valid_formula(): void
    {
        $this->assertTrue($this->engine->validate(
            '(attendance * 0.4) + (task_completion * 0.6)',
            ['attendance' => true, 'task_completion' => true]
        ));
    }

    /** @test */
    public function validate_returns_false_for_invalid_formula(): void
    {
        $this->assertFalse($this->engine->validate(
            'attendance * UNKNOWN_FUNC()',
            ['attendance' => true]
        ));
    }

    // -------------------------------------------------------------------------
    // Real-world SARH Formula Scenarios
    // -------------------------------------------------------------------------

    /** @test */
    public function it_evaluates_sarh_performance_formula(): void
    {
        // Formula: (attendance * 0.4) + (on_time_rate * 0.3) + (task_completion * 0.3)
        $result = $this->engine->evaluate(
            '(attendance * 0.4) + (on_time_rate * 0.3) + (task_completion * 0.3)',
            [
                'attendance'      => 100.0,
                'on_time_rate'    => 90.0,
                'task_completion' => 85.0,
            ]
        );
        // = 40 + 27 + 25.5 = 92.5
        $this->assertEqualsWithDelta(92.5, $result, 0.0001);
    }

    /** @test */
    public function it_evaluates_loss_formula(): void
    {
        // Formula: (absent_days * daily_wage) + (late_minutes * (hourly_wage / 60))
        $result = $this->engine->evaluate(
            '(absent_days * daily_wage) + (late_minutes * (hourly_wage / 60))',
            [
                'absent_days'  => 2.0,
                'daily_wage'   => 300.0,
                'late_minutes' => 45.0,
                'hourly_wage'  => 37.5,
            ]
        );
        // = 600 + (45 * 0.625) = 600 + 28.125 = 628.125
        $this->assertEqualsWithDelta(628.125, $result, 0.0001);
    }
}
