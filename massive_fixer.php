<?php

declare(strict_types=1);

$root = __DIR__;
$filesFixed = 0;

$patterns = [
    // 1. Fix static model corruption (Filament Resources)
    '#protected static \?string \$model = \$this->([a-z0-9_]+)->class;#i' => function($m) {
        return "protected static ?string \$model = " . ucfirst($m[1]) . "::class;";
    },

    // 2. Fix corrupted class names (e.g., Entertainment$this->event->class)
    '#\b([A-Z][a-z0-9_]*)\$this->([a-z0-9_]+)->class#i' => function($m) {
        return $m[1] . ucfirst($m[2]) . "::class";
    },

    // 3. Case for naked $this->event->class (without prefix)
    '#\b\$this->([a-z0-9_]+)->class#i' => function($m) {
        return ucfirst($m[1]) . "::class";
    },

    // 4. Fix corrupted Filament routes in arrays
    '#\bPages\\\\(List|Create|Edit|View|Manage)\$this->([a-z0-9_]+)s?->route#i' => function($m) {
        return "Pages\\\\" . $m[1] . ucfirst(rtrim($m[2], "s")) . "::route()";
    },

    // 5. Generic middleware corruptions
    '#AddQueuedCookiesTo\$this->response->class#i' => "AddQueuedCookiesToResponse::class",
    '#Start\$this->session->class#i' => "StartSession::class",
    '#Authenticate\$this->session->class#i' => "AuthenticateSession::class",
    '#ShareErrorsFrom\$this->session->class#i' => "ShareErrorsFromSession::class",
    '#DispatchServingFilament\$this->event->class#i' => "DispatchServingFilamentEvent::class",

    // 6. Fix incorrect property calls in providers/services (e.g. $this->log-> instead of Log::)
    '#\$this->log->#' => "Log::",
    '#\$this->route->#' => "Route::",
    '#\$this->db->#' => "DB::",
    '#\$this->cache->#' => "Cache::",
    '#\$this->redis->#' => "Redis::",
    '#\$this->auth->#' => "Auth::",
    '#\$this->gate->#' => "Gate::",
    '#\$this->schema->#' => "Schema::",
    '#\$this->storage->#' => "Storage::",
    '#\$this->mail->#' => "Mail::",
    '#\$this->queue->#' => "Queue::",
    '#\$this->event->#' => "Event::",
    '#\$this->cookie->#' => "Cookie::",
    '#\$this->filesystem->#' => "Storage::",
    '#\$this->validator->#' => "Validator::",
];

echo "Starting massive fix...\n";
$directory = new RecursiveDirectoryIterator($root);
$iterator = new RecursiveIteratorIterator($directory);

foreach ($iterator as $file) {
    if ($file->isDir() || $file->getExtension() !== "php" || $file->getFilename() === "massive_fixer.php" || str_contains($file->getRealPath(), "vendor")) {
        continue;
    }

    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);
    $originalContent = $content;

    foreach ($patterns as $pattern => $replacement) {
        if (is_string($replacement)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content = preg_replace_callback($pattern, $replacement, $content);
        }
    }

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        $filesFixed++;
        echo "Fixed: $filePath\n";
    }
}

echo "\nTotal files fixed: \$filesFixed\n";
