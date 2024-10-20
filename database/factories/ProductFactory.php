<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();
        $price = fake()->numberBetween(10000, 1000000);
        return [
            'user_id' => User::factory(),
            'image' => fake()->imageUrl(),
            'title' => $title,
            'slug' => str_replace(' ', '-', $title),
            'price' => $price,
            'price_with_discount' => fake()->randomElement([null, fake()->numberBetween(5000, $price)]),
            'expert' => fake()->paragraph(2),
            'description' => fake()->paragraph(4),
            'download_link' => fake()->randomElement([null, fake()->url()]),
        ];
    }
}
