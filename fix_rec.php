<?php
$content = file_get_contents('app/Jobs/RecommendationQualityJob.php');
$content = str_replace(
    'DB::transaction(function() use ($ctr, $conversionRate, $revenueLift, $avgCosineSimilarity, $date) { return DB::table(\'recommendation_quality_logs\')->insert([',
    'DB::transaction(function() use ($metrics) { DB::table(\'recommendation_quality_logs\')->insert([',
    $content
);
$content = str_replace(
    "        ]);\n\n        Log::info('Quality metrics logged'",
    "        ]);\n        });\n\n        Log::info('Quality metrics logged'",
    $content
);
file_put_contents('app/Jobs/RecommendationQualityJob.php', $content);
echo "Fixed";
