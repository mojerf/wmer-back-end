<?php

use App\Enums\OrderStatus;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('public'));

it('seeds a complete, consistent demo data set outside production', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('role', 'admin')->count())->toBe(1)
        ->and(Work::count())->toBe(24)
        ->and(Post::count())->toBe(32)
        ->and(Product::count())->toBe(18)
        ->and(Contact::count())->toBe(6)
        ->and(Comment::whereNull('parent_id')->count())->toBeGreaterThan(0)
        ->and(Comment::whereNotNull('parent_id')->count())->toBeGreaterThan(0);

    // every order status is represented, and totals match their lines
    expect(Order::pluck('status')->unique()->map->value->sort()->values()->all())
        ->toBe(collect(OrderStatus::cases())->map->value->sort()->values()->all());

    Order::with('products')->each(function (Order $order) {
        expect($order->total_price)->toBe((int) $order->products->sum('pivot.op_price'));
    });

    // covers exist on the public disk
    Storage::disk('public')->assertExists(Work::first()->image);
});

it('lets the demo customer sign in and the public API serves the seeded content', function () {
    $this->seed(DatabaseSeeder::class);

    $this->withHeaders(spa())->postJson('/api/v1/login', ['email' => 'user@example.test', 'password' => 'password'])
        ->assertOk();

    // curated items are the newest, and lists paginate (12 per page)
    $this->getJson('/api/v1/main-page')->assertOk()->assertJsonCount(4, 'works')
        ->assertJsonPath('posts.0.slug', 'deploy-checklist');
    $this->getJson('/api/v1/posts')->assertOk()->assertJsonPath('meta.last_page', 3);
    $this->getJson('/api/v1/posts/zoneless-angular')->assertOk();
});

it('creates only the administrator in production', function () {
    $this->app['env'] = 'production';

    // run the seeder directly: `db:seed` would ask for confirmation in production
    app(DatabaseSeeder::class)->run();

    expect(User::count())->toBe(1)
        ->and(User::first()->isAdmin())->toBeTrue()
        ->and(Work::count() + Post::count() + Product::count())->toBe(0);
});
