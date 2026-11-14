<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
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
