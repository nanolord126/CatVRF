<?php declare(strict_types=1);

// Replace all ->morphs() with explicit column definitions

$file = 'database/migrations/2026_03_23_000001_create_models_3d_table.php';

if (!file_exists($file)) {
    echo "❌ File not found: $file\n";
    exit(1);
}

$content = file_get_contents($file);

// Replace $table->morphs('modelable')->comment(...) 
// with two columns: modelable_type and modelable_id
$content = preg_replace(
    '/\$table->morphs\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*->comment\s*\(\s*[\'"]([^\'"]*)[\'"]\s*\)/i',
    "\$table->string('\$1_type', 255)->comment('Model type')\n            ->\$table->unsignedBigInteger('\$1_id')->comment('Model ID')",
    $content
);

// Also handle without comment
$content = preg_replace(
    '/\$table->morphs\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\);/i',
    "\$table->string('\$1_type', 255);\n            \$table->unsignedBigInteger('\$1_id');",
    $content
);

// Fix syntax error - remove the arrow
$content = str_replace(
    "->comment('Model type')\n            ->\$table->unsignedBigInteger",
    "->comment('Model type');\n            \$table->unsignedBigInteger",
    $content
);

file_put_contents($file, $content);
echo "✅ Fixed: " . basename($file) . "\n";
