<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserShift>
 */
class UserShiftFactory extends Factory
{
    protected $model = UserShift::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'shift_id'       => Shift::factory(),
            'assigned_by'    => null,
            'effective_from' => now()->subDays(30),
            'effective_to'   => null,
            'is_current'     => true,
            'reason'         => 'تعيين ابتدائي',
            'approved_at'    => now(),
            'approved_by'    => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'effective_from' => now()->subDays(60),
            'effective_to'   => now()->subDays(5),
            'is_current'     => false,
        ]);
    }

    public function current(): static
    {
        return $this->state(fn () => [
            'effective_from' => now()->subDays(10),
            'effective_to'   => null,
            'is_current'     => true,
        ]);
    }

    public function withApproval(int $assignedBy, int $approvedBy): static
    {
        return $this->state(fn () => [
            'assigned_by' => $assignedBy,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }
}
