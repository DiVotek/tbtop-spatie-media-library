<?php

return [
    'per_page' => 24,

    // Format must be one the core ImageEncoder supports: webp|jpeg|png.
    'conversion' => [
        'format' => 'webp',
        'quality' => 80,
    ],

    // fnmatch patterns. text/html is refused regardless — it is the
    // SVG-as-html XSS vector.
    'accept' => ['image/*'],

    // Timeout and host allowlist live in tbtop-admin.media.url_import — one
    // setting for every ingestion path.
    'url_import' => [
        'enabled' => true,
    ],
];
