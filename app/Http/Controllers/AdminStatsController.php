<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Work;

/** Numbers for the admin dashboard. */
class AdminStatsController extends Controller
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        $byStatus = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'counts' => [
                'users' => User::count(),
                'works' => Work::count(),
                'posts' => Post::count(),
                'products' => Product::count(),
                'orders' => Order::count(),
                'contacts' => Contact::count(),
            ],
            'orders_by_status' => collect(OrderStatus::cases())
                ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => (int) ($byStatus[$status->value] ?? 0)])
                ->all(),
            // Revenue only counts completed orders.
            'revenue' => (int) Order::where('status', OrderStatus::Completed)->sum('total_price'),
        ];
    }
}
