# ⚡ JEWELRY 3D - QUICK START GUIDE

## 🚀 5-MINUTE SETUP

### 1. Run Migration
```bash
php artisan migrate --path=database/migrations/2026_03_19_000000_create_3d_models_table.php
```

### 2. Seed Sample Data
```bash
php artisan tinker
>>> Database\Factories\Jewelry3DModelFactory::new()->count(5)->create()
```

### 3. Access Admin Panel
```
URL: /admin/jewelry-3d-models
Login: admin@catvrf.ru / password
```

---

## 📱 USAGE IN CODE

### Upload 3D Model
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

### Generate AR Link
```php
$arUrl = $service->generateARView($model->id);
// Returns: https://viewer.example.com/embed?model=...&ar=true&format=usdz
```

### Generate VR Link
```php
$vrUrl = $service->generateVRView($model->id);
// Returns: https://viewer.example.com/embed?model=...&vr=true&format=gltf
```

### Rotate Model
```php
$rotated = $service->rotate3DModel($model->id, 45, 90, 180);
// Returns: ['model_id' => 1, 'rotationX' => 45, 'rotationY' => 90, 'rotationZ' => 180]
```

### Change Material
```php
$service->changeMetalType($model->id, 'silver');
```

### Download Model
```php
$downloadUrl = $service->downloadModel($model->id, 'glb');
// Returns: storage/jewelry/models/ring.glb?format=glb
```

---

## 🎨 DISPLAY IN BLADE VIEW

### Simple Embed
```blade
<livewire:jewelry.jewelry-3d-viewer :modelId="$jewelry->id" />
```

### Custom Props
```blade
<livewire:jewelry.jewelry-3d-viewer 
    :modelId="$jewelry->id"
    :arMode="true"
    :vrMode="false"
/>
```

### With Event Listeners
```blade
<livewire:jewelry.jewelry-3d-viewer 
    :modelId="$jewelry->id"
    wire:listen="model-updated"
/>
```

---

## 🗄️ DATABASE QUERIES

### Get All Models for Item
```php
use App\Domains\Jewelry\Models\Jewelry3DModel;

$models = Jewelry3DModel::where('jewelry_item_id', $jewelryId)->get();
```

### Get AR-Compatible Models
```php
$arModels = Jewelry3DModel::where('ar_compatible', true)
    ->where('status', 'active')
    ->get();
```

### Filter by Material
```php
$goldModels = Jewelry3DModel::where('material_type', 'gold')
    ->where('tenant_id', filament()->getTenant()->id)
    ->get();
```

### Check Model Size
```php
$largeModels = Jewelry3DModel::where('file_size_mb', '>', 20)
    ->orderBy('file_size_mb', 'desc')
    ->get();
```

---

## 🔌 INTEGRATION EXAMPLES

### With WalletService
```php
// Charge for 3D model access
WalletService::debit(
    amount: 50000, // 500 rubles
    description: 'Premium 3D model access',
    correlation_id: $correlationId,
);
```

### With FraudMLService
```php
// Check before showing expensive model
$score = FraudMLService::scoreOperation(new OperationDto(
    type: 'jewelry_view_premium',
    user_id: $userId,
    item_value: 150000,
));

if ($score > 0.8) {
    abort(403, 'Suspicious activity detected');
}
```

### With RecommendationService
```php
// Recommend similar items based on 3D properties
$recommendations = RecommendationService::getForUser(
    userId: $userId,
    vertical: 'jewelry',
    context: [
        'material_type' => 'gold',
        'weight_range' => [4, 6],
    ]
);
```

---

## 📊 ADMIN PANEL FEATURES

### Create New 3D Model
1. Go to `/admin/jewelry-3d-models/create`
2. Select jewelry item
3. Upload model file (GLB/GLTF/USDZ/OBJ)
4. Upload texture (optional)
5. Set material type
6. Set weight in grams
7. Toggle AR/VR compatibility
8. Save

### Edit Existing Model
1. Go to `/admin/jewelry-3d-models`
2. Click pencil icon on row
3. Update any field
4. Save changes (audit logged)

### Filter Models
- By material type (Gold, Silver, Platinum, Rose Gold)
- By status (Uploaded, Processing, Active, Archived)
- By AR compatibility
- By VR compatibility

### Bulk Actions
- Delete multiple models
- Archive multiple models
- Export list as CSV

---

## 🎯 PERFORMANCE TIPS

### Optimize Model File Size
```bash
# Using gltf-pipeline tool
gltf-pipeline -i model.glb -o optimized.glb --draco
```

### Enable Caching
```php
$modelUrl = Cache::remember(
    "jewelry_model_{$modelId}",
    3600, // 1 hour
    fn() => $model->model_url
);
```

### Use CDN for Files
```env
CDN_URL=https://cdn.catvrf.ru/jewelry/
STORAGE_DISK=s3
```

---

## 🐛 TROUBLESHOOTING

### Model Not Loading
```php
// Check if model exists
$model = Jewelry3DModel::findOrFail($modelId);

// Verify file URL is accessible
$response = Http::get($model->model_url);
if ($response->failed()) {
    Log::error('Model file not accessible', [
        'model_id' => $modelId,
        'url' => $model->model_url,
    ]);
}
```

### AR/VR Not Working
```php
// Verify compatibility flags
if (!$model->ar_compatible) {
    throw new Exception('Model is not AR compatible');
}

// Check WebGL support
// Browser console: WebGL2RenderingContext
```

### Performance Issues
```php
// Monitor loading time
$start = microtime(true);
$model = Jewelry3DModel::findOrFail($modelId);
$time = microtime(true) - $start;

Log::info("Model query time: {$time}s");
```

---

## 📋 CHECKLIST BEFORE GOING LIVE

- [ ] All migrations run successfully
- [ ] Filament admin panel accessible
- [ ] Test 3D upload (GLB, GLTF, USDZ formats)
- [ ] Test AR mode on iPhone/Android
- [ ] Test VR mode on compatible headset
- [ ] Verify model rotation works smoothly
- [ ] Confirm zoom limits (0.1x - 10x)
- [ ] Test material switching
- [ ] Verify download functionality
- [ ] Check audit logs are recording
- [ ] Test tenant isolation
- [ ] Load test with 100 concurrent viewers
- [ ] Monitor disk space usage
- [ ] Backup 3D models to S3/cloud

---

## 📞 SUPPORT

**For issues:**
1. Check `storage/logs/laravel.log`
2. Review audit channel: `Log::channel('audit')`
3. Monitor Sentry errors
4. Contact DevOps team

**Quick debug:**
```php
// Tinker session
php artisan tinker
>>> $model = Jewelry3DModel::find(1)
>>> $model->toJson()
>>> Log::channel('audit')->info('Debug', ['model' => $model])
```

---

*Last Updated: 2026-03-19*
*Version: CANON 2026 v1.0*
