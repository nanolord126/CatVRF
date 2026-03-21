<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sql = DB::select("SELECT name FROM sqlite_master WHERE type=\"table\" AND name LIKE \"%message%\"");
print_r($sql);

