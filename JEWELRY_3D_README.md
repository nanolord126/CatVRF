# 💎 Jewelry 3D Visualization System

> Interactive 3D model viewer for jewelry items with AR/VR support, built with Laravel + Livewire + Three.js

## ✨ Features

### 🎨 Interactive 3D Viewer
- **Real-time 3D rotation** - Rotate on X, Y, Z axes
- **Smooth zoom** - 0.1x to 10x magnification
- **Material switching** - Gold, Silver, Platinum, Rose Gold
- **Preview generation** - Multi-angle captures
- **Mobile-optimized** - Touch-friendly controls

### 📱 AR/VR Support
- **Apple AR** - USDZ format for iOS Quick Look
- **Android AR** - WebAR with camera integration
- **VR Headsets** - WebXR compatible (Meta Quest, HTC Vive)
- **360° Views** - Immersive product experience

### 📥 Multi-Format Support
- **GLB** - Binary format (optimized, recommended)
- **GLTF** - ASCII format (human-readable)
- **USDZ** - Apple AR format
- **OBJ** - 3D printing format

### 🔧 Admin Management
- Upload 3D models via Filament Admin Panel
- Manage textures and previews
- Set material properties (weight, dimensions)
- Toggle AR/VR compatibility
- Filter by material type and status
- Bulk operations (delete, archive)

### 📊 Analytics & Tracking
- Audit logging with correlation IDs
- Tenant-aware access control
- Usage statistics and performance metrics
- Error tracking and monitoring

---

## 🚀 Installation

### Prerequisites
```bash
PHP 8.2+
Laravel 11
PostgreSQL 14+
Redis (cache & queue)
Livewire 3
```

### Step 1: Run Migration
```bash
php artisan migrate --path=database/migrations/2026_03_19_000000_create_3d_models_table.php
```

### Step 2: Seed Sample Data (Optional)
```bash
php artisan db:seed --class=Jewelry3DModelSeeder
# OR
php artisan tinker
>>> Database\Factories\Jewelry3DModelFactory::new()->count(10)->create()
```

### Step 3: Publish Assets
```bash
php artisan vendor:publish --provider="Livewire\LivewireServiceProvider"
```

### Step 4: Configure Storage
```env
# .env
FILESYSTEM_DISK=s3  # or 'local'
AWS_BUCKET=jewelry-models  # if using S3
AWS_REGION=us-east-1
AWS_ACCESS_KEY_ID=***
AWS_SECRET_ACCESS_KEY=***
```

---

## 📖 Usage

### 1. Upload 3D Model

**Via Admin Panel:**
1. Go to `/admin/jewelry-3d-models/create`
2. Select jewelry item
3. Upload `.glb`/`.gltf`/`.usdz` model file
4. Upload texture (optional)
5. Set material type and weight
6. Save

**Via Code:**
```php
use App\Domains\Jewelry\Services\Jewelry3DService;

$service = app(Jewelry3DService::class);

$model = $service->uploadModel([
    'jewelry_item_id' => 1,
    'model_file' => $request->file('model'),
    'texture_file' => $request->file('texture'),
    'preview_file' => $request->file('preview'),
    'material_type' => 'gold',
    'weight_grams' => 5.5,
    'format' => 'glb',
    'correlation_id' => \Illuminate\Support\Str::uuid(),
]);
```

### 2. Display 3D Viewer

**In Blade Template:**
```blade
<livewire:jewelry.jewelry-3d-viewer :modelId="$jewelry->id" />
```

**Full Example:**
```blade
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold">{{ $jewelry->name }}</h1>
    
    <livewire:jewelry.jewelry-3d-viewer 
        :modelId="$jewelry->id"
        wire:listen="model-updated"
    />
    
    <div class="mt-8">
        <p class="text-gray-300">{{ $jewelry->description }}</p>
        <p class="text-2xl font-bold text-amber-400">{{ $jewelry->price }} ₽</p>
        
        <button @click="addToCart" class="mt-4 px-6 py-2 bg-amber-500 rounded">
            Добавить в корзину
        </button>
    </div>
</div>
```

### 3. Generate AR Link

```php
// Get URL for iOS AR Quick Look
$arUrl = $service->generateARView($modelId);
// Returns: https://viewer.example.com/embed?model=...&ar=true&format=usdz

// Create AR button
<a href="{{ $arUrl }}" class="btn btn-ar">📱 View in AR</a>
```

### 4. Generate VR Link

```php
// Get URL for VR viewer
$vrUrl = $service->generateVRView($modelId);
// Returns: https://viewer.example.com/embed?model=...&vr=true&format=gltf

// Create VR button
<a href="{{ $vrUrl }}" class="btn btn-vr">🥽 View in VR</a>
```

### 5. Download Model

```php
$downloadUrl = $service->downloadModel($modelId, 'glb');
// User receives .glb file for 3D printing or CAD software
```

---

## 🏗️ Architecture

### Database Schema

```sql
CREATE TABLE 3d_models (
    id BIGINT PRIMARY KEY,
    uuid UUID UNIQUE,
    correlation_id UUID,
    tenant_id BIGINT,
    business_group_id BIGINT,
    jewelry_item_id BIGINT,
    
    -- Model Data
    model_url VARCHAR(255),
    texture_url VARCHAR(255),
    material_type ENUM('gold', 'silver', 'platinum', 'rose_gold'),
    dimensions JSON,
    weight_grams DECIMAL(8,2),
    preview_image_url VARCHAR(255),
    
    -- Compatibility
    ar_compatible BOOLEAN,
    vr_compatible BOOLEAN,
    file_size_mb DECIMAL(10,2),
    format ENUM('glb', 'gltf', 'usdz', 'obj'),
    
    -- Status
    status ENUM('uploaded', 'processing', 'active', 'archived'),
    tags JSON,
    
    -- Timestamps
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### Model Relationships

```php
// app/Domains/Jewelry/Models/Jewelry3DModel.php
class Jewelry3DModel extends Model
{
    public function jewelry(): BelongsTo
    {
        return $this->belongsTo(JewelryItem::class);
    }
}

// app/Domains/Jewelry/Models/JewelryItem.php
class JewelryItem extends Model
{
    public function models(): HasMany
    {
        return $this->hasMany(Jewelry3DModel::class);
    }
}
```

### Service Methods

| Method | Description | Returns |
|--------|-------------|---------|
| `uploadModel($data)` | Upload 3D model file | `Jewelry3DModel` |
| `generateARView($modelId)` | Get AR viewer URL | `string` |
| `generateVRView($modelId)` | Get VR viewer URL | `string` |
| `rotate3DModel($modelId, $x, $y, $z)` | Rotate model | `array` |
| `zoomModel($modelId, $level)` | Zoom model | `array` |
| `changeMetalType($modelId, $type)` | Change material | `Jewelry3DModel` |
| `downloadModel($modelId, $format)` | Download file | `string` |
| `createModelPreview($modelId, $angles)` | Generate previews | `array` |

---

## 🎨 Livewire Component

### Properties
- `modelId` (int) - 3D model ID
- `modelUrl` (string) - Model file URL
- `rotationX/Y/Z` (float) - Rotation angles
- `zoom` (float) - Zoom level
- `materialType` (string) - Metal type
- `arMode` (bool) - AR mode active
- `vrMode` (bool) - VR mode active

### Methods
- `loadModel()` - Load model from database
- `rotateX/Y/Z($angle)` - Set rotation
- `setZoom($level)` - Set zoom level
- `changeMaterial($material)` - Change metal type
- `enableAR()` - Enable AR mode
- `enableVR()` - Enable VR mode
- `downloadModel($format)` - Download file
- `shareModel()` - Get share URL

### Events
```php
// Listen to model updates
wire:listen="model-updated"

// Dispatch events
$this->dispatch('material-changed', material: 'gold');
$this->dispatch('model-shared', url: $shareUrl);
```

---

## 🔌 Integration Examples

### With Shopping Cart
```php
// app/Livewire/Marketplace/Cart.php
public function addItem($modelId)
{
    $model = Jewelry3DModel::findOrFail($modelId);
    $jewelry = $model->jewelry;
    
    $this->cart[] = [
        'jewelry_id' => $jewelry->id,
        'model_id' => $modelId,
        'material' => $model->material_type,
        'price' => $jewelry->price,
    ];
    
    $this->dispatch('item-added');
}
```

### With Wallet Service
```php
// Payment for premium 3D viewer access
WalletService::debit(
    amount: 50000, // 500 rubles
    description: 'Premium 3D model access',
    correlation_id: $correlationId,
);

Log::channel('audit')->info('Jewelry 3D model premium purchased', [
    'model_id' => $modelId,
    'user_id' => auth()->id(),
]);
```

### With Recommendations
```php
// Recommend similar jewelry based on material & weight
$recommendations = RecommendationService::getForUser(
    userId: $userId,
    vertical: 'jewelry',
    context: [
        'material_type' => $model->material_type,
        'weight_range' => [$model->weight_grams - 2, $model->weight_grams + 2],
    ]
);
```

---

## 📊 Performance Optimization

### Model File Optimization
```bash
# Install gltf-pipeline
npm install -g gltf-pipeline

# Optimize GLB file
gltf-pipeline -i input.glb -o output.glb --draco
```

### Caching Strategy
```php
// Cache model data for 1 hour
$model = Cache::remember("jewelry_model_{$modelId}", 3600, function () use ($modelId) {
    return Jewelry3DModel::findOrFail($modelId);
});
```

### CDN Integration
```env
CDN_URL=https://cdn.catvrf.ru/jewelry/
STORAGE_DISK=s3
```

### Lazy Loading
```blade
<!-- Load 3D viewer only when visible -->
<div x-intersect="$wire.loadModel()" class="3d-viewer-container">
    <livewire:jewelry.jewelry-3d-viewer :modelId="$modelId" wire:lazy />
</div>
```

---

## 🧪 Testing

### Factory Usage
```php
use Database\Factories\Jewelry3DModelFactory;

// Create single model
$model = Jewelry3DModelFactory::new()->create();

// Create multiple models
$models = Jewelry3DModelFactory::new()->count(10)->create();

// Create with specific attributes
$model = Jewelry3DModelFactory::new()->create([
    'material_type' => 'gold',
    'ar_compatible' => true,
]);
```

### Unit Tests
```php
use Tests\TestCase;

class Jewelry3DServiceTest extends TestCase
{
    public function test_upload_model()
    {
        $service = app(Jewelry3DService::class);
        $model = $service->uploadModel([...]);
        
        $this->assertInstanceOf(Jewelry3DModel::class, $model);
        $this->assertEquals('gold', $model->material_type);
    }
    
    public function test_rotate_model()
    {
        $result = $service->rotate3DModel($modelId, 45, 90, 180);
        
        $this->assertEquals(45, $result['rotationX']);
        $this->assertEquals(90, $result['rotationY']);
    }
}
```

---

## 🐛 Troubleshooting

### Model Not Loading
```php
// Check if model URL is accessible
$response = Http::get($model->model_url);
if ($response->failed()) {
    Log::error('Model file not found', ['url' => $model->model_url]);
}

// Verify model exists
$model = Jewelry3DModel::findOrFail($modelId);
```

### AR Not Working
```php
// Check AR compatibility flag
if (!$model->ar_compatible) {
    throw new Exception('Model not AR compatible');
}

// Check browser support
// Safari 15+ on iOS, Chrome 87+ on Android
```

### Performance Issues
```php
// Check query count
DB::enableQueryLog();
$model = Jewelry3DModel::with('jewelry')->find($modelId);
echo DB::getQueryLog(); // Should be 2 queries max

// Monitor memory usage
Log::info('Memory: ' . memory_get_usage() / 1024 / 1024 . ' MB');
```

---

## 📋 Deployment Checklist

- [ ] Database migration executed
- [ ] Storage configured (S3/local)
- [ ] Filament admin resource accessible
- [ ] Test model upload
- [ ] Verify AR/VR links work
- [ ] Test on mobile device
- [ ] Performance tested (load time < 2s)
- [ ] Audit logging enabled
- [ ] Monitoring dashboards active
- [ ] Backup strategy configured

---

## 📚 Resources

- [Three.js Documentation](https://threejs.org/docs)
- [Babylon.js Viewer](https://doc.babylonjs.com/features/featuresDeepDive/Babylon.js_viewer)
- [WebAR Best Practices](https://www.w3.org/TR/webxr-ar-module-1/)
- [USDZ Format](https://graphics.pixar.com/usdz/)
- [GLTF Specification](https://www.khronos.org/gltf/)

---

## 📞 Support

For issues or questions:

1. Check logs: `storage/logs/laravel.log`
2. Review audit: `Log::channel('audit')`
3. Monitor Sentry for errors
4. Contact: devops@catvrf.eu

---

**Version:** CANON 2026 v1.0  
**Last Updated:** 2026-03-19  
**Status:** ✅ Production Ready
