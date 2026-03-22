# CatVRF 3D SYSTEM - DEPLOYMENT & SETUP GUIDE

## 🚀 QUICK START

### 1. Generate 3D Services for All Verticals

```bash
php generate-3d-verticals.php
```

**Output**: Creates 41 `{Vertical}3DService` classes and 41 `{Vertical}3DViewer` components

### 2. Register API Routes

Add to `routes/api.php`:

```php
include base_path('routes/api-3d.php');
```

### 3. Publish Configuration

```bash
php artisan vendor:publish --tag=3d-config
```

### 4. Create Storage Directories

```bash
mkdir -p storage/app/public/3d-models
mkdir -p storage/app/public/3d-previews
php artisan storage:link
```

---

## 📋 IMPLEMENTATION CHECKLIST

### Backend Setup

- [ ] Run `php generate-3d-verticals.php`
- [ ] Include `routes/api-3d.php`
- [ ] Publish configuration
- [ ] Create storage directories
- [ ] Run migrations (if any)
- [ ] Install Three.js library

### 3D Model Management

- [ ] Upload 3D models to `/storage/app/public/3d-models/`
- [ ] Organize by vertical: `/3d-models/{vertical}/{sku}.glb`
- [ ] Create previews/thumbnails
- [ ] Test model loading

### Frontend Integration

- [ ] Include Three.js in `<head>`
- [ ] Include AR.js for mobile
- [ ] Register Livewire components
- [ ] Test 3D rendering

### Testing & QA

- [ ] Test on desktop browsers
- [ ] Test on mobile devices (iOS/Android)
- [ ] Test AR functionality
- [ ] Performance optimization
- [ ] Security audit

### Production Deployment

- [ ] Enable CDN for model distribution
- [ ] Configure caching headers
- [ ] Setup monitoring
- [ ] Prepare rollback plan

---

## 🎯 DETAILED IMPLEMENTATION

### Step 1: Backend Service Setup

#### Generate Services

```bash
php generate-3d-verticals.php
```

Output:

```
✅ Created: Auto3DService.php
✅ Created: Beauty3DService.php
✅ Created: Furniture3DService.php
... (41 total)
```

#### Verify Generated Files

```bash
ls -la app/Services/3D/
ls -la app/Livewire/3D/
```

### Step 2: API Configuration

#### 1. Update `routes/api.php`

```php
<?php
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api')->group(function () {
    require base_path('routes/api-3d.php');
});
```

#### 2. Create `routes/api-3d.php` (Already Done ✅)

Contains all 3D endpoints:

- `/api/v1/3d/products/*`
- `/api/v1/3d/rooms/*`
- `/api/v1/3d/vehicles/*`
- `/api/v1/3d/furniture/*`

### Step 3: Model Storage Setup

#### Create Directory Structure

```bash
mkdir -p storage/app/public/3d-models
mkdir -p storage/app/public/3d-previews
mkdir -p storage/app/public/3d-models/Auto
mkdir -p storage/app/public/3d-models/Jewelry
mkdir -p storage/app/public/3d-models/Furniture
mkdir -p storage/app/public/3d-models/Hotels
mkdir -p storage/app/public/3d-models/RealEstate
# ... create directories for each vertical
```

#### Create Symbolic Link

```bash
php artisan storage:link
```

#### Upload Models

```bash
# Upload .glb files to:
# storage/app/public/3d-models/{vertical}/{product-sku}.glb

# Example:
# storage/app/public/3d-models/Auto/tesla-model-3.glb
# storage/app/public/3d-models/Jewelry/diamond-ring-001.glb
```

### Step 4: Frontend Integration

#### 1. Add CDN Libraries to `app.blade.php`

```blade
<!-- Three.js for 3D Rendering -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>

<!-- GLTF Loader for 3D Models -->
<script src="https://cdn.jsdelivr.net/npm/three@latest/examples/js/loaders/GLTFLoader.js"></script>

<!-- AR.js for Mobile AR -->
<script src="https://cdn.jsdelivr.net/npm/ar.js@latest/three.js/ar.js"></script>

<!-- Livewire for Components -->
@livewireScripts
```

#### 2. Register Livewire Components

```php
// app/Providers/AppServiceProvider.php

public function boot(): void
{
    Livewire::component('3d.product-card', ProductCard3D::class);
    Livewire::component('3d.room-tour', Room3DTour::class);
    Livewire::component('3d.property-viewer', Property3DViewer::class);
    Livewire::component('3d.clothing-fitting', ClothingFittingRoom::class);
    Livewire::component('3d.vehicle-configurator', VehicleConfigurator::class);
    Livewire::component('3d.furniture-ar', FurnitureAR::class);
    Livewire::component('3d.jewelry-display', Jewelry3DDisplay::class);
}
```

#### 3. Use in Blade Views

```blade
<livewire:3d.product-card :product-id="1" vertical="Electronics" />
<livewire:3d.room-tour :room-id="101" hotel-id="1" />
<livewire:3d.property-viewer :property-id="1" />
```

### Step 5: Testing

#### Unit Tests

```bash
php artisan test tests/Feature/ThreeDVisualizationTest.php
```

#### Manual Testing

```bash
# Test 3D API endpoints
curl http://localhost:8000/api/v1/3d/products/1

# Test Room Visualization
curl -X POST http://localhost:8000/api/v1/3d/rooms/1/visualize \
  -H "Content-Type: application/json" \
  -d '{"type":"suite","length":5,"width":4,"height":2.8}'

# Test Vehicle Configurator
curl -X POST http://localhost:8000/api/v1/3d/vehicles/1/visualize \
  -H "Content-Type: application/json" \
  -d '{"brand":"Tesla","model":"Model 3","color":"#000000"}'
```

---

## 🔧 CONFIGURATION TUNING

### Performance Optimization

#### 1. Canvas Settings (`config/3d.php`)

```php
'performance' => [
    'enable_shadows' => true,      // High quality but slower
    'enable_reflection' => false,  // Mobile: disable
    'lod_enabled' => true,         // Lower detail at distance
    'max_textures' => 16,
    'texture_compression' => 'bc1',
],
```

#### 2. Mobile Optimization

```php
// In 3D Component
if ($isMobile) {
    $quality = 'low';
    $textureSize = 512;
    $maxPolyCount = 50000;
} else {
    $quality = 'high';
    $textureSize = 2048;
    $maxPolyCount = 500000;
}
```

#### 3. Loading Strategy

```php
// Progressive Loading
1. Show thumbnail (instant)
2. Show low-detail model (1s)
3. Show full model (3-5s)
```

---

## 🌐 DEPLOYMENT TO PRODUCTION

### 1. CDN Setup

#### Upload Models to CDN

```bash
# Example: AWS S3 + CloudFront
aws s3 cp storage/app/public/3d-models s3://catvrf-3d-models/ --recursive

# Update config
3D_CDN_URL=https://d111111abcdef8.cloudfront.net/
```

#### Update Model URLs

```php
// In ProductCard3D.php
$modelUrl = config('3d.cdn_url') . "{$vertical}/{$sku}.glb";
```

### 2. Caching Headers

```php
// In API Controller
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=86400')  // 24 hours
    ->header('ETag', md5(json_encode($data)));
```

### 3. Monitoring

#### Monitor 3D Performance

```php
// Log rendering times
Log::channel('3d')->info('Model loaded', [
    'product_id' => $productId,
    'load_time_ms' => $elapsed,
    'device' => $userAgent,
]);
```

#### Setup Alerts

```php
if ($loadTimeMs > 5000) {
    Sentry::captureMessage('Slow 3D model load: ' . $productId);
}
```

### 4. Rollback Plan

```bash
# If 3D rendering fails, fallback to 2D
# In ProductCard3D.php
if ($threeDFailed) {
    $this->dispatch('fallback-to-2d');
}
```

---

## 📱 MOBILE & AR SETUP

### iOS Setup

```swift
// Enable camera access in Info.plist
<key>NSCameraUsageDescription</key>
<string>We need camera access for AR visualization</string>
```

### Android Setup

```xml
<!-- AndroidManifest.xml -->
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.INTERNET" />
```

### AR.js Configuration

```javascript
// In Blade view
window.ARConfig = {
    sourceType: 'webcam',
    sourceWidth: window.innerWidth,
    sourceHeight: window.innerHeight,
    displayWidth: window.innerWidth,
    displayHeight: window.innerHeight,
    cameraParametersUrl: '/camera_para.dat',
    maxDetectionRate: 30,
};
```

---

## 🔐 SECURITY CONSIDERATIONS

### 1. Authentication

```php
// All 3D endpoints require auth
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/api/v1/3d/products/{id}/upload', ...);
});
```

### 2. File Upload Validation

```php
$request->validate([
    '3d_model' => 'required|file|mimes:glb,gltf|max:100000',
]);
```

### 3. Rate Limiting

```php
// Limited to 1000 requests/hour
RateLimiter::for('3d-api', function (Request $request) {
    return Limit::perHour(1000)->by($request->user()->id);
});
```

### 4. CORS Configuration

```php
// config/cors.php
'allowed_origins' => [
    'https://catvrf.com',
    'https://app.catvrf.com',
],
```

---

## 📊 MONITORING & ANALYTICS

### 1. Performance Metrics

```php
// Track 3D rendering performance
MetricsCollector::record('3d.model.load_time', $milliseconds);
MetricsCollector::record('3d.ar.enabled_count', $enabledCount);
MetricsCollector::record('3d.error_rate', $errorPercentage);
```

### 2. User Analytics

```php
// Track 3D feature usage
event(new ThreeDModelViewed($productId, $userId, $device));
event(new ARViewActivated($productId, $userId, $device));
```

### 3. Error Tracking

```php
try {
    $visualization = $this->service->generateVisualization($data);
} catch (Exception $e) {
    Sentry::captureException($e);
    Log::error('3D generation failed', ['error' => $e->getMessage()]);
}
```

---

## ✅ FINAL CHECKLIST

### Pre-Launch

- [ ] All 41 verticals have 3D services
- [ ] All API endpoints tested
- [ ] 3D models uploaded to CDN
- [ ] Mobile AR functionality verified
- [ ] Performance tested (< 3s load time)
- [ ] Security audit completed
- [ ] Monitoring configured

### Launch

- [ ] Deploy to production
- [ ] Enable 3D rendering globally
- [ ] Monitor error rates
- [ ] Gather user feedback
- [ ] Optimize based on metrics

### Post-Launch

- [ ] Weekly performance reviews
- [ ] Monthly model updates
- [ ] Quarterly feature expansions
- [ ] Continuous optimization

---

## 📚 DOCUMENTATION LINKS

- **3D System Report**: `3D_SYSTEM_REPORT_PHASE1.md`
- **Config Reference**: `config/3d.php`
- **API Documentation**: `routes/api-3d.php`
- **Component Examples**: `/resources/views/livewire/3d/`
- **Tests**: `tests/Feature/ThreeDVisualizationTest.php`

---

## 🆘 TROUBLESHOOTING

### Issue: Models not loading

```bash
# Check storage symlink
php artisan storage:link
# Verify models in storage/app/public/3d-models/
ls -la storage/app/public/3d-models/
```

### Issue: AR not working on mobile

```bash
# Check HTTPS (AR requires HTTPS)
# Verify camera permissions
# Test on different browser
```

### Issue: Slow rendering

```bash
# Reduce max_textures in config
# Disable shadows for lower-end devices
# Use progressive loading
```

---

**Status**: 🟢 READY FOR DEPLOYMENT  
**Next Step**: Run `php generate-3d-verticals.php` and upload 3D models
