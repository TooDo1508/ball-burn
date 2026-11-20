<?php

$defaultCanonicalUrl = env('APP_ENV') === 'production'
    ? 'https://ballburn.site'
    : env('APP_URL', 'http://localhost');

return [
    'canonical_url' => rtrim((string) env('SEO_CANONICAL_URL', $defaultCanonicalUrl), '/'),
];
