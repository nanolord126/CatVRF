<?php
$content = file_get_contents('app/Jobs/RecommendationQualityJob.php');
$content = preg_replace('/\{\s+DB::transaction\(function\(\) use \(\$metrics\) \{\s*DB::table\(\'recommendation_quality_logs\'\)->insert/s', '{ DB::table(\'recommendation_quality_logs\')->insert', $content);
$content = preg_replace('/\]\);\s+\}\);\s+\}\);\s+\}\);/s', ']);', $content);

$content = preg_replace('/DB::table\(\'recommendation_quality_logs\'\)->insert\(\[/s', "DB::transaction(function() use (\$metrics) {\n            DB::table('recommendation_quality_logs')->insert([", $content);
$content = preg_replace("/created_at' => now\(\),\n        \]\);/s", "created_at' => now(),\n            ]);\n        });", $content);
file_put_contents('app/Jobs/RecommendationQualityJob.php', $content);
echo "Fixed via regex";
