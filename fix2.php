<?php

$dirs = ['Finances', 'Events', 'Gifts', 'SportingGoods'];
foreach($dirs as $dir) {
    $path = __DIR__ . "/app/Domains/$dir/Services";
    foreach(glob($path . '/*.php') as $file) {
        $content = file_get_contents($file);
        $content = str_replace('Log::channel("audit")', "Log::channel('audit')", $content);
        $content = str_replace('$this->fraudControlService->check', 'FraudControlService::check', $content);
        file_put_contents($file, $content);
    }
}
