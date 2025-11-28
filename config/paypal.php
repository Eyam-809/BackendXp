<?php
/**
 * PayPal Setting & API Credentials
 * Created by Raza Mehdi <srmk@outlook.com>.
 */

return [
    'mode'    => env('PAYPAL_MODE','sandbox'),

    'sandbox' => [
        'client_id'         => env('PAYPAL_SANDBOX_CLIENT_ID'),
        'client_secret'     => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        'app_id'            => '',
    ],

    'live' => [
        'client_id'         => env('PAYPAL_LIVE_CLIENT_ID'),
        'client_secret'     => env('PAYPAL_LIVE_CLIENT_SECRET'),
        'app_id'            => '',
    ],

    'payment_action' => 'Sale',
    'currency'       => 'MXN',
    'notify_url'     => '',
    'locale'         => 'es_MX',
];

