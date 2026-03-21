<?php
$content = file_get_contents('app/Jobs/DemandForecastJob.php');
$content = preg_replace('/DemandModelVersion::create\(\[/s', "DB::transaction(function() use (\$version, \$metrics) {\n            return DemandModelVersion::create([", $content);
$content = preg_replace('/\"\);\s+\/\/ Активировать модель/s', "\");\n            });\n\n            // Активировать модель", $content);
$content = preg_replace('/DemandForecast::create\(\[/s', "DB::transaction(function() use (\$product, \$forecastDate, \$result, \$this) {\n                    return DemandForecast::create([", $content);
$content = preg_replace('/now\(\)\,\s+\]\);\s+\}/s', "now(),\n                    ]);\n                });\n            }", $content);
$content = preg_replace('/\$deleteCount = DemandForecast::query\(\)/s', "\$deleteCount = DB::transaction(function() {\n            return DemandForecast::query()", $content);
$content = preg_replace('/delete\(\);\s+if \(\$deleteCount/s', "delete();\n        });\n\n        if (\$deleteCount", $content);
file_put_contents('app/Jobs/DemandForecastJob.php', $content);
