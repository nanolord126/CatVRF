<?php
$f = __DIR__ . "/app/Domains/FarmDirect/Services/FarmDirectService.php";
$c = file_get_contents($f);
if (substr(trim($c), -1) === "}") {
    $c = rtrim($c, " \t\n\r\0\x0B}");
}
// Strip another if needed, or just append properly:
$c = $c . "\n    }\n}\n";
file_put_contents($f, $c);

