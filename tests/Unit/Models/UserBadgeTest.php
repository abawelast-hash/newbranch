<?php

namespace Tests\Unit\Models;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBadgeTest extends TestCase
{
    use RefreshDatabase;

    // ── Relationships ────────────────────────────────────────────────

    public function test_user_has_many_badges(): void
    {
        $user   = User::factory()->create();
        $badge1 = Badge::factory()->create();
        $badge2 = Badge::factory()->create();

        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge1->id]);
        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge2->id]);

        $this->assertCount(2, $user->badges);
        $this->assertInstanceOf(UserBadge::class, $user->badges->first());
    }

    public function test_badge_has_many_awards(): void
    {
        $badge = Badge::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserBadge::factory()->create(['user_id' => $user1->id, 'badge_id' => $badge->id]);
        UserBadge::factory()->create(['user_id' => $user2->id, 'badge_id' => $badge->id]);

        $this->assertCount(2, $badge->awards);
        $this->assertInstanceOf(UserBadge::class, $badge->awards->first());
    }

    public function test_user_badge_belongs_to_badge(): void
    {
        $badge     = Badge::factory()->create();
        $userBadge = UserBadge::factory()->create(['badge_id' => $badge->id]);

        $this->assertInstanceOf(Badge::class, $userBadge->badge);
        $this->assertEquals($badge->id, $userBadge->badge->id);
    }

    public function test_awarded_badges_eager_loads_badge(): void
    {
        $user  = User::factory()->create();
        $badge = Badge::factory()->create();

        UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge->id]);

        $awardedBadges = $user->awardedBadges()->get();

        $this->assertCount(1, $awardedBadges);
        $this->assertTrue($awardedBadges->first()->relationLoaded('badge'));
    }

    // ── Business Logic ──────────────────────────────────────────────

    public function test_award_creates_record_and_adds_points(): void
    {
        $user    = User::factory()->create(['total_points' => 100]);
        $badge   = Badge::factory()->withPoints(50)->create();
        $manager = User::factory()->create();

        $userBadge = UserBadge::award($user->id, $badge->id, $manager->id, 'إنجاز مميز');

        $this->assertDatabaseHas('user_badges', [
            'user_id'        => $user->id,
            'badge_id'       => $badge->id,
            'awarded_reason' => 'إنجاز مميز',
            'awarded_by'     => $manager->id,
        ]);

        $this->assertEquals(150, $user->fresh()->total_points);
    }

    public function test_award_with_zero_points_badge(): void
    {
        $user    = User::factory()->create(['total_points' => 100]);
        $badge   = Badge::factory()->withPoints(0)->create();
        $manager = User::factory()->create();

        UserBadge::award($user->id, $badge->id, $manager->id, 'شارة تقديرية');

        $this->assertEquals(100, $user->fresh()->total_points);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function test_scope_awarded_between(): void
    {
        $user  = User::factory()->create();
        $badge = Badge::factory()->create();

        UserBadge::factory()->create([
            'user_id'    => $user->id,
            'badge_id'   => $badge->id,
            'awarded_at' => now()->subDays(5),
        ]);

        UserBadge::factory()->create([
            'user_id'    => $user->id,
            'badge_id'   => $badge->id,
            'awarded_at' => now()->subDays(30),
        ]);

        $recent = UserBadge::awardedBetween(now()->subDays(10), now())->get();
        $this->assertCount(1, $recent);
    }

    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $badge = Badge::factory()->create();

        UserBadge::factory()->create(['user_id' => $user1->id, 'badge_id' => $badge->id]);
        UserBadge::factory()->create(['user_id' => $user2->id, 'badge_id' => $badge->id]);

        $this->assertCount(1, UserBadge::forUser($user1->id)->get());
    }
}
