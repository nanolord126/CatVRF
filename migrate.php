<?php declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$exitCode = $app->make(\Illuminate\Contracts\Console\Kernel::class)->call('migrate', ['--force' => true]);

exit($exitCode);
