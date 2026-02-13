<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        $category = fake()->randomElement(['attendance', 'finance', 'performance', 'special']);

        return [
            'name_ar'        => fake('ar_SA')->randomElement(['بطل الحضور', 'نجم الأداء', 'مميز الشهر', 'خبير المالية']),
            'name_en'        => fake()->randomElement(['Attendance Hero', 'Performance Star', 'Employee of Month', 'Finance Expert']),
            'slug'           => fake()->unique()->slug(2),
            'description_ar' => fake('ar_SA')->sentence(),
            'description_en' => fake()->sentence(),
            'icon'           => 'heroicon-o-star',
            'color'          => fake()->hexColor(),
            'category'       => $category,
            'points_reward'  => fake()->randomElement([10, 25, 50, 100]),
            'criteria'       => null,
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function withPoints(int $points): static
    {
        return $this->state(fn () => ['points_reward' => $points]);
    }
}
