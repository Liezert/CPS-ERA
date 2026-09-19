<?php

/**
 * Probe cepat untuk start.sh: apakah host:port database bisa dijangkau lewat TCP?
 *
 * Laravel menganggap "Connection timed out" sebagai lost connection dan mencoba ulang,
 * di level koneksi maupun query, sehingga satu `artisan migrate` ke host yang tak
 * terjangkau menghabiskan 4x PDO::ATTR_TIMEOUT (~20 dtk). Probe ini hanya satu kali
 * percobaan, jadi retry di start.sh gagal cepat saat DB memang tidak bisa dijangkau.
 *
 * Exit 0 = terjangkau (atau tidak relevan untuk driver ini), 1 = tidak terjangkau.
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$name = config('database.default');
$config = (new Illuminate\Support\ConfigurationUrlParser)
    ->parseConfiguration(config("database.connections.{$name}", []));

if (($config['driver'] ?? null) === 'sqlite') {
    exit(0);
}

if (! empty($config['unix_socket'])) {
    exit(file_exists($config['unix_socket']) ? 0 : 1);
}

$host = $config['host'] ?? '127.0.0.1';
$port = (int) ($config['port'] ?? 3306);
$timeout = (int) ($config['options'][PDO::ATTR_TIMEOUT] ?? 5);

$socket = @fsockopen($host, $port, $errno, $error, $timeout);

if ($socket === false) {
    fwrite(STDERR, "Database {$host}:{$port} tidak terjangkau: {$error}\n");
    exit(1);
}

fclose($socket);
exit(0);
