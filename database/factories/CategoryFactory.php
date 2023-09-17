<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->randomElement(['Aluguel', 'Automovel', 'Cartão', 'Lote', 'Prestação']),
            'type' => $this->faker->randomElement(['expense', 'revenue']),
            'user_id' => 1,
        ];
    }
}
