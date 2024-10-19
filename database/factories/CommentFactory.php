<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $models = [
            Post::class,
            Product::class,
            Work::class,
        ];

        return [
            'commentable_type' => fake()->randomElement($models),
            'commentable_id' => fake()->numberBetween(1, 100),
            'user_id' => User::factory(),
            // 'parent_id' => Comment::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
