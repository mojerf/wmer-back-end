<?php

namespace App\Payments;

final readonly class VerifyResult
{
    public function __construct(
        public bool $paid,
        public ?string $reference = null,
    ) {}
}
