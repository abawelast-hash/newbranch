<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        $start = fake()->randomElement(['06:00', '07:00', '08:00', '09:00', '14:00', '22:00']);
        $isOvernight = in_array($start, ['22:00']);

        $endMap = [
            '06:00' => '14:00',
            '07:00' => '15:00',
            '08:00' => '16:00',
            '09:00' => '17:00',
            '14:00' => '22:00',
            '22:00' => '06:00',
        ];

        return [
            'name_ar'              => fake('ar_SA')->randomElement(['الصباحي', 'المسائي', 'الليلي', 'المرن']),
            'name_en'              => fake()->randomElement(['Morning', 'Evening', 'Night', 'Flexible']),
            'start_time'           => $start,
            'end_time'             => $endMap[$start],
            'grace_period_minutes' => fake()->randomElement([5, 10, 15]),
            'is_overnight'         => $isOvernight,
            'is_active'            => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function overnight(): static
    {
        return $this->state(fn () => [
            'start_time'   => '22:00',
            'end_time'     => '06:00',
            'is_overnight' => true,
        ]);
    }
}
