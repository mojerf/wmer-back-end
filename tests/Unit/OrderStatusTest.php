<?php

use App\Enums\OrderStatus;

it('grants downloads only for completed orders', function () {
    expect(OrderStatus::Completed->grantsDownloads())->toBeTrue();

    foreach ([OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Cancelled] as $status) {
        expect($status->grantsDownloads())->toBeFalse();
    }
});
