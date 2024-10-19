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
        $users = User::factory(50)->create();
        Contact::factory(10)->create();
        Order::factory(100)->make()->each(function ($order) use ($users, $products) {
            $order->user_id = $users->random()->id;
            $order->product_id = $products->random()->id;
            $order->save();
        });

        foreach ($posts as $post) {
            $this->seedCommentsAndReplies($post, Post::class);
        }
        foreach ($works as $work) {
            $this->seedCommentsAndReplies($work, Work::class);
        }
        foreach ($products as $product) {
            $this->seedCommentsAndReplies($product, Product::class);
        }
    }


    private function seedCommentsAndReplies($modelInstance, $modelType)
    {
        $comments = Comment::factory(5)->make([
            'commentable_type' => $modelType,
            'commentable_id' => $modelInstance->id,
        ]);

        $modelInstance->comments()->saveMany($comments);

        foreach ($comments->random(3) as $comment) {
            Comment::factory(2)->make([
                'parent_id' => $comment->id,
                'commentable_type' => $modelType,
                'commentable_id' => $modelInstance->id,
            ])->each(function ($reply) use ($comment) {
                $comment->replies()->save($reply);
            });
        }
    }
}
