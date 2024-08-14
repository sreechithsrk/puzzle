<?php

namespace Database\Factories;

use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Score>
 */
class ScoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Students::factory(),
            'total_score' => $this->faker->numberBetween(0, 14),
            'string' => $this->faker->lexify('?????????????'),
            'remaining_string' => $this->faker->lexify('?????????????'),
        ];
    }
}
