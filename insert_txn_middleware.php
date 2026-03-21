<?php

$appPhp = file_get_contents('bootstrap/app.php');
if (strpos($appPhp, 'EnforceDbTransaction::class') === false) {
    if (strpos($appPhp, "->withMiddleware(function (Middleware \$middleware) {\n") !== false) {
        $search = "->withMiddleware(function (Middleware \$middleware) {\n";
    } else {
        $search = "->withMiddleware(function (Middleware \$middleware): void {\n";
    }
    
    $replace = $search . "        \$middleware->append(\App\Http\Middleware\EnforceDbTransaction::class);\n";
    file_put_contents('bootstrap/app.php', str_replace($search, $replace, $appPhp));
    echo "Injected Transaction Middleware\n";
} else {
    echo "Already injected\n";
}