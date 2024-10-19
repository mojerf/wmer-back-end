<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Work>
 */
class WorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'image' => fake()->imageUrl(),
            'title' => $title,
            'slug' => str_replace(' ', '-', $title),
            'timeline' => 'شهریور 1403 - مهر 1403',
            'publish_date' => 'مهر 1403',
            'role' => 'Front-End , Back-End Developer',
            'tags' => "Angular,Laravel,TS",
            'project_link' => fake()->randomElement([null, fake()->url()]),
            'full_image' => fake()->randomElement([null, fake()->imageUrl()]),
            'overview' => fake()->randomElement([null, fake()->paragraph(2)]),
            'learn' => fake()->randomElement([null, fake()->paragraph(2)]),
            'description' => fake()->paragraph(4),
        ];
    }
}
