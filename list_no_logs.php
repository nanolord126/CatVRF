<?php
$r = json_decode(file_get_contents('report.json'), true);
foreach ($r['issues'] as $i) {
   if (!$i['analysis']['has_logging']) {
      echo $i['path'] . PHP_EOL;
   }
}
