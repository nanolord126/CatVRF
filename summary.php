<?php
$r = json_decode(file_get_contents('report.json'), true);
$issues = $r['issues'] ?? [];

$syntax = 0;
$empty = 0;
$nologs = 0;
$notry = 0;

foreach ($issues as $i) {
   if ($i['analysis']['has_syntax_error']) $syntax++;
   if ($i['analysis']['empty_methods'] > 0) $empty++;
   if (!$i['analysis']['has_logging']) $nologs++;
   if (!$i['analysis']['has_error_handling']) $notry++;
}

echo "Total analyzed missing canon metrics:" . PHP_EOL;
echo "Syntax Errors: $syntax" . PHP_EOL;
echo "Empty Methods: $empty" . PHP_EOL;
echo "No Logs: $nologs" . PHP_EOL;
echo "No TryCatch: $notry" . PHP_EOL;
