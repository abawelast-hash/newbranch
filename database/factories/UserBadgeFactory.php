<?php

namespace Database\Factories;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBadge>
 */
class UserBadgeFactory extends Factory
{
    protected $model = UserBadge::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'badge_id'       => Badge::factory(),
            'awarded_at'     => now(),
            'awarded_reason' => 'تحقيق هدف تجريبي',
            'awarded_by'     => null,
        ];
    }

    public function withReason(string $reason): static
    {
        return $this->state(fn () => ['awarded_reason' => $reason]);
    }

    public function awardedBy(int $userId): static
    {
        return $this->state(fn () => ['awarded_by' => $userId]);
    }
}
