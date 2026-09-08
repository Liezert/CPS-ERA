<?php

// Pastikan struktur direktori /tmp untuk Laravel storage dibuat di lingkungan serverless Vercel
$storageDirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/storage/app/public',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Forward request ke entry point public/index.php Laravel
require __DIR__ . '/../public/index.php';
