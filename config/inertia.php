<?php

return [
    'history' => ['encrypt' => true],
    'ssr' => ['enabled' => false],
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [resource_path('js/pages')],
        'page_extensions' => ['vue'],
    ],
];
