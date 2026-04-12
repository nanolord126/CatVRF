<?php
/**
 * CatVRF 2026 — Mass migration from old JS composable stores to Pinia TS stores.
 *
 * Replaces:
 *   1. Import paths:  ../../Stores/auth.js  → @/stores  (barrel)
 *   2. Function names: useAuthStore → useAuth, useBusinessStore → useTenant,
 *                      useNotificationStore → useNotifications
 *   3. Removes `.state.` wrapper:  auth.state.user → auth.user
 *   4. Removes `.value` from old computed refs that become Pinia getters:
 *      auth.isB2BMode.value → auth.isB2BMode
 *
 * Run:  php migrate_to_pinia.php
 */

$baseDir = __DIR__ . '/resources/js';

// ── 1. Find all .vue files ────────────────────────────────
$vueFiles = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if ($file->getExtension() === 'vue') {
        $vueFiles[] = $file->getPathname();
    }
}

echo "📁 Found " . count($vueFiles) . " .vue files\n";

$totalFixes = 0;
$filesFixed = 0;
$details = [];

foreach ($vueFiles as $filePath) {
    $original = file_get_contents($filePath);
    $content = $original;
    $fixes = 0;
    $shortPath = str_replace(__DIR__ . '/', '', str_replace('\\', '/', $filePath));

    // ─── STEP A: Replace import lines ─────────────────────
    // Collect which stores this file imports
    $needsAuth = false;
    $needsTenant = false;
    $needsNotifications = false;

    if (preg_match('/import\s*\{\s*useAuthStore\s*\}/', $content)) {
        $needsAuth = true;
    }
    if (preg_match('/import\s*\{\s*useBusinessStore\s*\}/', $content)) {
        $needsTenant = true;
    }
    if (preg_match('/import\s*\{\s*useNotificationStore\s*\}/', $content)) {
        $needsNotifications = true;
    }

    if (!$needsAuth && !$needsTenant && !$needsNotifications) {
        continue; // no old store imports
    }

    // Remove old import lines
    $importPatterns = [
        '/^\s*import\s*\{\s*useAuthStore\s*\}\s*from\s*[\'"][^"\']*Stores\/auth\.js[\'"];?\s*$/m',
        '/^\s*import\s*\{\s*useBusinessStore\s*\}\s*from\s*[\'"][^"\']*Stores\/business\.js[\'"];?\s*$/m',
        '/^\s*import\s*\{\s*useNotificationStore\s*\}\s*from\s*[\'"][^"\']*Stores\/notifications\.js[\'"];?\s*$/m',
    ];
    foreach ($importPatterns as $pat) {
        $content = preg_replace($pat, '', $content, -1, $c);
        $fixes += $c;
    }

    // Build new import line
    $imports = [];
    if ($needsAuth) $imports[] = 'useAuth';
    if ($needsTenant) $imports[] = 'useTenant';
    if ($needsNotifications) $imports[] = 'useNotifications';
    $newImport = "import { " . implode(', ', $imports) . " } from '@/stores'";

    // Check if there is already a @/stores import (some components might already have it)
    if (preg_match('/import\s*\{[^}]*\}\s*from\s*[\'"]@\/stores[\'"]/', $content)) {
        // Merge with existing @/stores import
        $content = preg_replace_callback(
            '/import\s*\{\s*([^}]*)\s*\}\s*from\s*[\'"]@\/stores[\'"]/',
            function ($m) use ($imports) {
                $existing = array_map('trim', explode(',', $m[1]));
                $merged = array_unique(array_merge($existing, $imports));
                return "import { " . implode(', ', $merged) . " } from '@/stores'";
            },
            $content
        );
        $fixes++;
    } else {
        // Insert new import after the last import line in <script> section
        // Find a good insertion point — after the last import statement
        if (preg_match('/^(import\s+.+)$/m', $content, $m, PREG_OFFSET_CAPTURE)) {
            // Find the last import line
            preg_match_all('/^import\s+.+$/m', $content, $allImports, PREG_OFFSET_CAPTURE);
            $lastImport = end($allImports[0]);
            $insertPos = $lastImport[1] + strlen($lastImport[0]);
            $content = substr($content, 0, $insertPos) . "\n" . $newImport . substr($content, $insertPos);
            $fixes++;
        }
    }

    // ─── STEP B: Replace function calls ────────────────────
    // useAuthStore() → useAuth()
    $content = preg_replace('/\buseAuthStore\s*\(\s*\)/', 'useAuth()', $content, -1, $c);
    $fixes += $c;
    // useBusinessStore() → useTenant()
    $content = preg_replace('/\buseBusinessStore\s*\(\s*\)/', 'useTenant()', $content, -1, $c);
    $fixes += $c;
    // useNotificationStore() → useNotifications()
    $content = preg_replace('/\buseNotificationStore\s*\(\s*\)/', 'useNotifications()', $content, -1, $c);
    $fixes += $c;

    // ─── STEP C: Remove .state. wrapper ────────────────────
    // Pattern: (varName).state.(property) → (varName).(property)
    // We handle specific variable names used across the codebase
    $stateVarNames = ['auth', 'biz', 'business', 'notif', 'notifs', 'notifications', 'notify'];
    foreach ($stateVarNames as $var) {
        // Match in templates and script: var.state.xxx → var.xxx
        $content = preg_replace(
            '/\b' . preg_quote($var, '/') . '\.state\./',
            $var . '.',
            $content, -1, $c
        );
        $fixes += $c;
    }

    // ─── STEP D: Remove .value from Pinia getters ──────────
    // Old composable computed refs require .value, Pinia getters don't
    // auth computed getters that had .value:
    $authGetters = [
        'isAuthenticated', 'isTenantOwner', 'isB2BMode',
        'creditAvailable', 'userName', 'tenantName',
        'businessGroupName', 'avatarUrl',
    ];
    foreach ($stateVarNames as $var) {
        foreach ($authGetters as $getter) {
            // Match: var.getter.value → var.getter
            // But NOT var.getter.value.something (i.e. .value must be at boundary)
            $content = preg_replace(
                '/\b' . preg_quote($var, '/') . '\.' . preg_quote($getter, '/') . '\.value\b/',
                $var . '.' . $getter,
                $content, -1, $c
            );
            $fixes += $c;
        }
    }

    // Notification getters: .unreadCount.value, .recent.value
    $notifGetters = ['unreadCount', 'recent'];
    foreach ($stateVarNames as $var) {
        foreach ($notifGetters as $getter) {
            $content = preg_replace(
                '/\b' . preg_quote($var, '/') . '\.' . preg_quote($getter, '/') . '\.value\b/',
                $var . '.' . $getter,
                $content, -1, $c
            );
            $fixes += $c;
        }
    }

    // Business getters: .totalEmployees.value, etc.
    $bizGetters = ['totalEmployees', 'activeWarehouses', 'activeCampaigns', 'pendingOrders'];
    foreach ($stateVarNames as $var) {
        foreach ($bizGetters as $getter) {
            $content = preg_replace(
                '/\b' . preg_quote($var, '/') . '\.' . preg_quote($getter, '/') . '\.value\b/',
                $var . '.' . $getter,
                $content, -1, $c
            );
            $fixes += $c;
        }
    }

    // ─── STEP E: Clean up duplicate empty lines ────────────
    $content = preg_replace('/\n{3,}/', "\n\n", $content);

    // ─── Save ──────────────────────────────────────────────
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        $filesFixed++;
        $totalFixes += $fixes;
        $details[] = "  ✅ {$shortPath} ({$fixes} fixes)";
        echo "  ✅ {$shortPath} — {$fixes} fixes\n";
    }
}

echo "\n" . str_repeat('─', 60) . "\n";
echo "🎯 Total: {$totalFixes} fixes in {$filesFixed} files\n";
echo str_repeat('─', 60) . "\n";

// ── Verification: check for remaining old patterns ─────────
echo "\n🔍 Verification...\n";
$remaining = 0;
foreach ($vueFiles as $filePath) {
    $content = file_get_contents($filePath);
    $shortPath = str_replace(__DIR__ . '/', '', str_replace('\\', '/', $filePath));

    if (preg_match('/Stores\/auth\.js|Stores\/business\.js|Stores\/notifications\.js/', $content)) {
        echo "  ⚠️  Old import remaining: {$shortPath}\n";
        $remaining++;
    }
    if (preg_match('/useAuthStore|useBusinessStore|useNotificationStore/', $content)) {
        echo "  ⚠️  Old function remaining: {$shortPath}\n";
        $remaining++;
    }
    if (preg_match('/\b(auth|biz|business|notif|notifs|notifications|notify)\.state\./', $content)) {
        echo "  ⚠️  .state. remaining: {$shortPath}\n";
        $remaining++;
    }
}

if ($remaining === 0) {
    echo "  ✅ 0 remaining violations — migration complete!\n";
} else {
    echo "  ⚠️  {$remaining} violations remain — manual review needed\n";
}
