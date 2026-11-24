<?php

return [
    'environment' => env('SEPAY_ENVIRONMENT', 'live'),
    'checkout_enabled' => filter_var(env('SEPAY_CHECKOUT_ENABLED', false), FILTER_VALIDATE_BOOL),
    'auto_confirm_enabled' => filter_var(env('SEPAY_AUTO_CONFIRM_ENABLED', false), FILTER_VALIDATE_BOOL),
    'reprocess_enabled' => filter_var(env('SEPAY_REPROCESS_ENABLED', true), FILTER_VALIDATE_BOOL),
    'webhook_api_key' => env('SEPAY_WEBHOOK_API_KEY'),
    'bank_code' => env('SEPAY_BANK_CODE', 'VPBank'),
    'account_number' => env('SEPAY_ACCOUNT_NUMBER'),
    'payment_prefix' => env('SEPAY_PAYMENT_PREFIX', 'BBPAY'),
];
