<?php
$content = file_get_contents('app/Jobs/RecommendationQualityJob.php');
$content = preg_replace("/created_at' => now\(\),\r?\n        \]\);/is", "created_at' => now(),\n        ]);\n        });", $content);
file_put_contents('app/Jobs/RecommendationQualityJob.php', $content);
echo "Fixed";
