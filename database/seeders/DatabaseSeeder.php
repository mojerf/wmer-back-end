<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()
            ->create([
                'first_name' => 'مجتبی',
                'last_name' => 'عرفان راد',
                'email' => 'mojerf@yahoo.com',
                'phone_number' => '09134456735',
                'role' => 'admin'
            ]);

        $products = Product::factory(20)->for($admin)->create();
        $posts = Post::factory(20)->for($admin)->create();
        $works = Work::factory(20)->for($admin)->create();
        $users = User::factory(49)->create();
        Contact::factory(10)->create();
        Order::factory(100)->create();

        foreach ($posts as $post) {
            $this->seedCommentsAndReplies($post, Post::class, $users);
        }
        foreach ($works as $work) {
            $this->seedCommentsAndReplies($work, Work::class, $users);
        }
        foreach ($products as $product) {
            $this->seedCommentsAndReplies($product, Product::class, $users);
        }
    }


    private function seedCommentsAndReplies($modelInstance, $modelType, $users)
    {
        $newCommentCounter = fake()->numberBetween(0, 5);
        $comments = Comment::factory($newCommentCounter)->make([
            'commentable_type' => $modelType,
            'commentable_id' => $modelInstance->id,
        ])->each(function ($comment) use ($users) {
            $comment->user_id = $users->random()->id;
            $comment->save();
        });

        $modelInstance->comments()->saveMany($comments);

        foreach ($comments->random(fake()->numberBetween(0, $newCommentCounter)) as $comment) {
            Comment::factory(fake()->numberBetween(1, 3))->make([
                'parent_id' => $comment->id,
                'commentable_type' => $modelType,
                'commentable_id' => $modelInstance->id,
            ])->each(function ($reply) use ($comment, $users) {
                $comment->user_id = $users->random()->id;
                $comment->replies()->save($reply);
            });
        }
    }
}
