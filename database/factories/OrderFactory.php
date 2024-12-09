<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $users = User::pluck('id')->toArray();

        return [
            'user_id' => fake()->randomElement($users),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled']),
            'total_price' => fake()->numberBetween(10, 1000) * 1000,
        ];
    }
}
