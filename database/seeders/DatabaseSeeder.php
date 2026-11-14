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
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The admin account comes from the environment (ADMIN_EMAIL / ADMIN_PASSWORD), never from the repository.
        $password = config('app.admin.password') ?? (app()->isLocal() ? 'password' : Str::password(24));
        $admin = User::factory()->admin()->create([
            'first_name' => 'مجتبی',
            'last_name' => 'عرفان‌راد',
            'email' => config('app.admin.email'),
            'phone_number' => null,
            'password' => $password,
        ]);

        if (! config('app.admin.password') && ! app()->isLocal()) {
            $this->command?->warn("Generated admin password (shown once): {$password}");
        }

        $products = Product::factory(20)->for($admin)->create();
        $posts = Post::factory(20)->for($admin)->create();
        $works = Work::factory(20)->for($admin)->create();
        $users = User::factory(49)->create();
        $orders = Order::factory(100)->recycle($users)->create();
        Contact::factory(25)->create();

        foreach ($orders as $order) {
            $orderProducts = $products->random(rand(1, 5));
            $orderTotal = 0;
            foreach ($orderProducts as $orderProduct) {
                $op_price = fake()->randomElement([$orderProduct->price, fake()->numberBetween(10, 1000) * 1000]);
                $order->products()->attach(
                    $orderProduct->id,
                    ['op_price' => $op_price]
                );
                $orderTotal += $op_price;
            }
            $order->total_price = $orderTotal;
            $order->save();
        }
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
