<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Customers see their own orders; administrators see all. */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /** Only the customer can pay, and only while the order is waiting for payment. */
    public function pay(User $user, Order $order): bool
    {
        return $order->user_id === $user->id && $order->status === OrderStatus::Pending;
    }
}
