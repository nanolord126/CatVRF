<?php
$file = __DIR__ . "/app/Domains/FarmDirect/Services/FarmDirectService.php";
$content = file_get_contents($file);
$content = str_replace("            ->get();\n\n}", "            ->get();\n    }\n}", $content);
file_put_contents($file, $content);
echo "Fixed!\n";
