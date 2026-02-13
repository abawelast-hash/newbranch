<?php

namespace Tests\Unit\Models;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserShiftTest extends TestCase
{
    use RefreshDatabase;

    // ── Relationships ────────────────────────────────────────────────

    public function test_user_can_have_multiple_shifts_history(): void
    {
        $user   = User::factory()->create();
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();

        UserShift::factory()->expired()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift1->id,
        ]);

        UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift2->id,
        ]);

        $this->assertCount(2, $user->shifts);
    }

    public function test_active_shift_returns_current_assignment(): void
    {
        $user  = User::factory()->create();
        $shift = Shift::factory()->create();

        UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        $activeShift = $user->activeShift();

        $this->assertNotNull($activeShift);
        $this->assertInstanceOf(UserShift::class, $activeShift);
        $this->assertEquals($shift->id, $activeShift->shift_id);
    }

    public function test_current_shift_returns_shift_model_for_backward_compat(): void
    {
        $user  = User::factory()->create();
        $shift = Shift::factory()->create();

        UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        $result = $user->currentShift();

        $this->assertNotNull($result);
        $this->assertInstanceOf(Shift::class, $result);
        $this->assertEquals($shift->id, $result->id);
    }

    public function test_current_shift_returns_null_when_no_assignment(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->currentShift());
        $this->assertNull($user->activeShift());
    }

    public function test_expired_shift_not_returned_as_active(): void
    {
        $user  = User::factory()->create();
        $shift = Shift::factory()->create();

        UserShift::factory()->expired()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        $this->assertNull($user->activeShift());
        $this->assertNull($user->currentShift());
    }

    // ── Shift → Assignments ─────────────────────────────────────────

    public function test_shift_has_assignments_relationship(): void
    {
        $shift = Shift::factory()->create();
        $user  = User::factory()->create();

        UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        $this->assertCount(1, $shift->assignments);
        $this->assertInstanceOf(UserShift::class, $shift->assignments->first());
    }

    // ── Business Logic ──────────────────────────────────────────────

    public function test_terminate_sets_end_date_and_clears_current(): void
    {
        $userShift = UserShift::factory()->current()->create();

        $userShift->terminate('انهاء تجريبي');

        $fresh = $userShift->fresh();
        $this->assertNotNull($fresh->effective_to);
        $this->assertFalse($fresh->is_current);
        $this->assertEquals('انهاء تجريبي', $fresh->reason);
    }

    public function test_make_current_deactivates_other_assignments(): void
    {
        $user   = User::factory()->create();
        $shift1 = Shift::factory()->create();
        $shift2 = Shift::factory()->create();

        $assignment1 = UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift1->id,
        ]);

        $assignment2 = UserShift::factory()->create([
            'user_id'    => $user->id,
            'shift_id'   => $shift2->id,
            'is_current' => false,
        ]);

        $assignment2->makeCurrent();

        $this->assertFalse($assignment1->fresh()->is_current);
        $this->assertTrue($assignment2->fresh()->is_current);
    }

    public function test_is_valid_on_date(): void
    {
        $userShift = UserShift::factory()->create([
            'effective_from' => now()->subDays(10),
            'effective_to'   => now()->addDays(10),
        ]);

        $this->assertTrue($userShift->isValidOn(now()));
        $this->assertFalse($userShift->isValidOn(now()->subDays(15)));
        $this->assertFalse($userShift->isValidOn(now()->addDays(15)));
    }

    public function test_is_valid_on_open_ended_assignment(): void
    {
        $userShift = UserShift::factory()->create([
            'effective_from' => now()->subDays(10),
            'effective_to'   => null,
        ]);

        $this->assertTrue($userShift->isValidOn(now()));
        $this->assertTrue($userShift->isValidOn(now()->addDays(100)));
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function test_scope_active_filters_correctly(): void
    {
        $user  = User::factory()->create();
        $shift = Shift::factory()->create();

        // Active
        UserShift::factory()->current()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        // Expired
        UserShift::factory()->expired()->create([
            'user_id'  => $user->id,
            'shift_id' => $shift->id,
        ]);

        $this->assertCount(1, UserShift::active()->where('user_id', $user->id)->get());
    }

    public function test_scope_for_user_in_period(): void
    {
        $user  = User::factory()->create();
        $shift = Shift::factory()->create();

        UserShift::factory()->create([
            'user_id'        => $user->id,
            'shift_id'       => $shift->id,
            'effective_from' => now()->subDays(20),
            'effective_to'   => now()->subDays(10),
        ]);

        $inRange  = UserShift::forUserInPeriod($user->id, now()->subDays(25), now()->subDays(8))->get();
        $outRange = UserShift::forUserInPeriod($user->id, now()->subDays(50), now()->subDays(30))->get();

        $this->assertCount(1, $inRange);
        $this->assertCount(0, $outRange);
    }
}
