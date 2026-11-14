<?php

namespace App\Payments;

/** What a gateway returns when a payment is registered: its token and where to send the customer. */
final readonly class StartResult
{
    public function __construct(
        public string $authority,
        public string $redirectUrl,
    ) {}
}
