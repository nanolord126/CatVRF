<?php
$f1 = "app/Http/Controllers/Api/PaymentController.php";
if (file_exists($f1)) {
    echo "Fixing $f1...\n";
    $c1 = file_get_contents($f1);
    $c1 = preg_replace("/}\s*'payment_id' => \\$payment->id,.*?'correlation_id' => \\$correlationId,\s*], 400\);\s*}\s*}/s", "}\n    }", $c1);
    file_put_contents($f1, $c1);
}

$f2 = "app/Http/Controllers/Api/V2/Analytics/MLAnalyticsController.php";
if (file_exists($f2)) {
    echo "Fixing $f2...\n";
    $c2 = file_get_contents($f2);
    $c2 = str_replace("$ltv Service", "$ltvService", $c2);
    file_put_contents($f2, $c2);
}

$f3 = "app/Http/Controllers/Internal/PaymentWebhookController.php";
if (file_exists($f3)) {
    echo "Fixing $f3...\n";
    $c3 = file_get_contents($f3);
    $c3 = str_replace("\\\->input()", "\->input()", $c3);
    file_put_contents($f3, $c3);
}

$files3D = [
    "app/Http/Controllers/Api/V1/Furniture3DController.php",
    "app/Http/Controllers/Api/V1/Product3DController.php",
    "app/Http/Controllers/Api/V1/Room3DController.php",
    "app/Http/Controllers/Api/V1/Vehicle3DController.php"
];
foreach($files3D as $f3d) {
    if (file_exists($f3d)) {
        echo "Fixing 3D NS in $f3d...\n";
        $c3d = file_get_contents($f3d);
        $c3d = str_replace("App\\Services\\3D", "App\\Services\\ThreeD", $c3d);
        file_put_contents($f3d, $c3d);
    }
}
echo "Syntax fixes complete.\n";
