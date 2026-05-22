<?php

return [
    'images' => [
        'max_size_kb' => (int) env('UPLOAD_IMAGE_MAX_SIZE_KB', 20480),
        'max_width' => (int) env('UPLOAD_IMAGE_MAX_WIDTH', 20000),
        'max_height' => (int) env('UPLOAD_IMAGE_MAX_HEIGHT', 20000),
        'max_pixels' => (int) env('UPLOAD_IMAGE_MAX_PIXELS', 150000000),
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],
];
