<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

// Just create app without routing
$app = new \Illuminate\Foundation\Application(dirname(__DIR__));

echo "OK\n";
