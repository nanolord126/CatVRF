<?php declare(strict_types=1);

use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the command...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

ob_start();
$status = $app->handleCommand(new ArgvInput);
$output = ob_get_clean();

if (strlen($output) > 0) {
    error_log("COMMAND OUTPUT LENGTH: " . strlen($output));
    error_log("FIRST 500 CHARS:\n" . substr($output, 0, 500));
    echo $output;
}

exit($status);
