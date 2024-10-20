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
        $products = Product::pluck('id')->toArray();

        return [
            'user_id' => fake()->randomElement($users),
            'product_id' => fake()->randomElement($products),
            'state' => 'state',
            'price' => fake()->numberBetween(10000, 1000000),
        ];
    }
}
