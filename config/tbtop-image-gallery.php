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

    'url_import' => [
        'enabled' => true,
        'timeout' => 30,

        // Empty = any host the core SsrfGuard allows. Populate to restrict
        // imports to known sources.
        'allowed_hosts' => [],
    ],
];
