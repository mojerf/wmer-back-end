<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateway
    |--------------------------------------------------------------------------
    |
    | "fake" approves every payment instantly (local development and tests only: it is refused in
    | production). "zarinpal" talks to Zarinpal; set ZARINPAL_MERCHANT_ID, and keep ZARINPAL_SANDBOX=true
    | until you are ready to take real money.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => (bool) env('ZARINPAL_SANDBOX', true),
        // Amounts are stored in tomans.
        'currency' => 'IRT',
    ],

];
