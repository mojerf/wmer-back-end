<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone_number' => fake()->randomElement([null, fake()->phoneNumber()]),
            'email' => fake()->email(),
            'title' => fake()->sentence(),
            'text' => fake()->paragraph(),
        ];
    }
}
