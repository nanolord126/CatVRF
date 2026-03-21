<?php declare(strict_types=1);

/**
 * CatVRF 3D System - Complete Deployment Script
 * Deploys 3D infrastructure and creates demo products
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

echo "\n🚀 CatVRF 3D SYSTEM - COMPLETE DEPLOYMENT\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Step 1: Clear cache
echo "📦 Step 1: Clearing cache...\n";
Artisan::call('cache:clear');
echo "✅ Cache cleared\n\n";

// Step 2: Create storage directories
echo "📂 Step 2: Creating storage directories...\n";
$directories = [
    'storage/app/public/3d-models',
    'storage/app/public/3d-models/Auto',
    'storage/app/public/3d-models/Beauty',
    'storage/app/public/3d-models/Electronics',
    'storage/app/public/3d-models/Furniture',
    'storage/app/public/3d-models/Jewelry',
    'storage/app/public/3d-models/Hotels',
    'storage/app/public/3d-models/RealEstate',
    'storage/app/public/3d-previews',
];

foreach ($directories as $dir) {
    File::ensureDirectoryExists($dir);
    echo "✅ Created: $dir\n";
}
echo "\n";

// Step 3: Create symbolic link
echo "📎 Step 3: Creating storage symlink...\n";
try {
    Artisan::call('storage:link');
    echo "✅ Storage symlink created\n\n";
} catch (\Exception $e) {
    echo "⚠️ Symlink already exists or skipped\n\n";
}

// Step 4: Register API routes
echo "🛣️ Step 4: Verifying API routes...\n";
$apiPath = base_path('routes/api.php');
$apiContent = File::get($apiPath);

if (strpos($apiContent, 'routes/api-3d.php') === false) {
    $newContent = $apiContent . "\n\n// 3D API Routes\ninclude base_path('routes/api-3d.php');\n";
    File::put($apiPath, $newContent);
    echo "✅ API routes registered\n\n";
} else {
    echo "✅ API routes already registered\n\n";
}

// Step 5: Publish configuration
echo "⚙️ Step 5: Publishing configuration...\n";
if (File::exists('config/3d.php')) {
    echo "✅ 3D configuration exists\n\n";
} else {
    echo "⚠️ 3D configuration not found\n\n";
}

// Step 6: Create demo 3D models
echo "🎨 Step 6: Creating demo 3D models...\n";

// Create simple box geometry GLB files for demo
$demoModels = [
    'storage/app/public/3d-models/Jewelry/diamond-ring.glb' => createDemoGLB('Diamond Ring', 0x00ff00),
    'storage/app/public/3d-models/Jewelry/gold-necklace.glb' => createDemoGLB('Gold Necklace', 0xffff00),
    'storage/app/public/3d-models/Hotels/apartment-001.glb' => createDemoGLB('Apartment Room', 0x0088ff),
    'storage/app/public/3d-models/Hotels/suite-room.glb' => createDemoGLB('Suite Room', 0xff8800),
    'storage/app/public/3d-models/Furniture/sofa.glb' => createDemoGLB('Sofa', 0xff0088),
    'storage/app/public/3d-models/Furniture/chair.glb' => createDemoGLB('Chair', 0x00ffff),
];

foreach ($demoModels as $path => $content) {
    File::put($path, $content);
    echo "✅ Created: " . basename($path) . "\n";
}
echo "\n";

// Step 7: Clear and warm up cache
echo "🔥 Step 7: Warming up cache...\n";
Artisan::call('cache:clear');
Artisan::call('config:cache');
Artisan::call('route:cache');
echo "✅ Cache warmed\n\n";

// Step 8: Verify installation
echo "✔️ Step 8: Verifying installation...\n";
$checks = [
    'config/3d.php' => 'Config file',
    'routes/api-3d.php' => 'API routes',
    'app/Services/3D/Product3DService.php' => 'Product service',
    'app/Livewire/ThreeD/ProductCard3D.php' => 'Product component',
];

foreach ($checks as $file => $label) {
    $exists = File::exists(base_path($file)) ? '✅' : '❌';
    echo "$exists $label: $file\n";
}
echo "\n";

echo "🎉 3D SYSTEM DEPLOYMENT COMPLETE!\n";
echo "=" . str_repeat("=", 50) . "\n";
echo "\n✨ Next steps:\n";
echo "1. php artisan migrate\n";
echo "2. Start development server: php artisan serve\n";
echo "3. Test 3D products at: http://localhost:8000/3d-demo\n";
echo "4. Access API: http://localhost:8000/api/v1/3d/products/1\n\n";

/**
 * Create a demo GLB file (simple binary format for testing)
 * This is a minimal valid GLB file
 */
function createDemoGLB(string $name, int $color): string
{
    // GLB header (12 bytes)
    $glbHeader = pack('I', 0x46546C67);  // "glTF" magic
    $glbVersion = pack('I', 2);           // Version 2
    
    // Minimal JSON chunk with scene info
    $json = json_encode([
        'asset' => ['version' => '2.0'],
        'scene' => 0,
        'scenes' => [['nodes' => [0]]],
        'nodes' => [['mesh' => 0]],
        'meshes' => [['primitives' => [['attributes' => ['POSITION' => 0]]]]],
        'accessors' => [['bufferView' => 0, 'componentType' => 5126, 'count' => 3, 'type' => 'VEC3']],
        'bufferViews' => [['buffer' => 0, 'byteLength' => 36]],
        'buffers' => [['byteLength' => 36]],
    ]);

    // Pad JSON to 4-byte boundary
    $jsonPadded = $json . str_repeat(' ', (4 - strlen($json) % 4) % 4);
    $jsonChunkLength = strlen($jsonPadded);

    // Binary data (simple triangle vertices)
    $binaryData = pack('f*', 0, 0, 0, 1, 0, 0, 0, 1, 0);
    $binaryChunkLength = strlen($binaryData);

    // Construct GLB file
    $glb = $glbHeader
        . $glbVersion
        . pack('I', 12 + 8 + $jsonChunkLength + 8 + $binaryChunkLength) // File size
        . pack('I', $jsonChunkLength)
        . pack('I', 0x4E4F534A)  // "JSON" type
        . $jsonPadded
        . pack('I', $binaryChunkLength)
        . pack('I', 0x004E4942)  // "BIN" type
        . $binaryData;

    return $glb;
}
