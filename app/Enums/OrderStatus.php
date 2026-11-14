<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Digital products (download links) are only handed out for completed orders. */
    public function grantsDownloads(): bool
    {
        return $this === self::Completed;
    }
}
