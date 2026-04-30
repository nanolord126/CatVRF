# CatVRF Fashion PBR Texture Generation - Complete Guide

## Overview

This guide covers the complete implementation of PBR (Physically Based Rendering) texture generation pipeline using ControlNet + Stable Diffusion for the CatVRF Fashion vertical. The system generates realistic textures (Albedo, Normal, Roughness, Metallic, AO, Displacement) from 2D product photographs.

## Architecture

### Clean Architecture Layers

```
modules/Fashion/
├── Domain/
│   ├── Entities/
│   │   └── TexturePack.php
│   ├── DTOs/
│   │   ├── GenerateTextureDTO.php
│   │   └── TextureResultDTO.php
│   ├── ValueObjects/
│   │   ├── TextureType.php
│   │   └── TextureGenerationStatus.php
│   └── Repositories/
│       └── TexturePackRepositoryInterface.php
├── Infrastructure/
│   ├── Models/
│   │   └── TexturePackModel.php
│   └── Repositories/
│       └── EloquentTexturePackRepository.php
└── Application/
    └── Services/
        ├── TextureGenerationService.php
        └── Generate3DFromPhotosService.php
```

### Key Components

1. **TexturePack Entity**: Domain entity representing a set of PBR textures
2. **TextureGenerationService**: Core service for ControlNet-based texture generation
3. **Generate3DFromPhotosService**: End-to-end 3D model generation with textures
4. **Repository Pattern**: Interface-based repository for data persistence
5. **Livewire Monitor**: Real-time monitoring dashboard for Filament

## Installation

### Prerequisites

- PHP 8.3+
- Laravel 11+
- Stable Diffusion API (Automatic1111 or ComfyUI)
- GPU with 24GB+ VRAM for training
- Redis for queue management

### Database Migration

```bash
php artisan migrate
```

This creates the `texture_packs` table with all necessary fields for PBR texture storage.

### Configuration Files

1. **config/fashion_textures.php**: Main configuration for texture generation
2. **config/fashion_lora_config.json**: LoRA model weights and settings
3. **config/fashion_lora_training.toml**: Training configuration for custom LoRA

### Environment Variables

Add these to your `.env` file:

```env
# Stable Diffusion API
STABLE_DIFFUSION_API_URL=http://localhost:7860
STABLE_DIFFUSION_API_KEY=your_api_key_here
STABLE_DIFFUSION_TIMEOUT=600

# Mesh Generation API
MESH_GENERATION_API_URL=http://localhost:8000
MESH_GENERATION_API_KEY=your_mesh_api_key_here

# ControlNet
CONTROLNET_ENABLED=true
CONTROLNET_CANNY_MODEL=control_v11p_sd15_canny
CONTROLNET_CANNY_WEIGHT=0.85
CONTROLNET_DEPTH_MODEL=control_v11f1p_sd15_depth
CONTROLNET_DEPTH_WEIGHT=0.70
CONTROLNET_OPENPOSE_MODEL=control_v11p_sd15_openpose
CONTROLNET_OPENPOSE_WEIGHT=0.70

# IP-Adapter
IP_ADAPTER_ENABLED=true
IP_ADAPTER_MODEL=ip-adapter_sd15
IP_ADAPTER_WEIGHT=0.8

# LoRA
LORA_ENABLED=true
LORA_BASE_PATH=storage/app/loras
LORA_DEFAULT_STRENGTH=0.75

# Storage
TEXTURE_STORAGE_DISK=public
TEXTURE_STORAGE_PATH=textures
TEXTURE_MAX_FILE_SIZE=10485760

# Queue
TEXTURE_QUEUE_CONNECTION=redis
TEXTURE_QUEUE_NAME=texture-generation
TEXTURE_QUEUE_MAX_TRIES=3
TEXTURE_QUEUE_TIMEOUT=600

# Quality
TEXTURE_MIN_QUALITY_SCORE=0.6
TEXTURE_HIGH_QUALITY_THRESHOLD=0.8
TEXTURE_VALIDATE_ON_GENERATION=true

# Retry
TEXTURE_RETRY_MAX_ATTEMPTS=3
TEXTURE_RETRY_DELAY=30
TEXTURE_RETRY_BACKOFF=2.0
```

## Usage

### Basic Texture Generation

```php
use Modules\Fashion\Application\Services\TextureGenerationService;
use Modules\Fashion\Domain\DTOs\GenerateTextureDTO;
use Modules\Fashion\Domain\ValueObjects\TextureType;

$service = app(TextureGenerationService::class);

$dto = GenerateTextureDTO::create(
    model3dId: 1,
    productName: 'Premium Cotton T-Shirt',
    materialType: TextureType::FABRIC,
    photoPaths: [
        'products/tshirt_front.jpg',
        'products/tshirt_back.jpg',
        'products/tshirt_detail.jpg',
    ],
);

$texturePack = $service->generatePBRTextures($dto);
```

### Complete 3D Model Generation with Textures

```php
use Modules\Fashion\Application\Services\Generate3DFromPhotosService;
use Modules\Fashion\Domain\ValueObjects\TextureType;

$service = app(Generate3DFromPhotosService::class);

$result = $service->generateFromPhotos(
    photoPaths: [
        'products/jacket_front.jpg',
        'products/jacket_back.jpg',
        'products/jacket_side.jpg',
        'products/jacket_detail.jpg',
        'products/jacket_collar.jpg',
        'products/jacket_sleeve.jpg',
        'products/jacket_zipper.jpg',
        'products/jacket_inside.jpg',
    ],
    productName: 'Leather Jacket',
    materialType: TextureType::LEATHER,
    tenantId: 1,
    businessGroupId: null,
);

// Result contains:
// - model_3d_id
// - texture_pack_uuid
// - status
// - correlation_id
// - estimated_completion_time
```

### Monitoring Generation Progress

```php
$service = app(Generate3DFromPhotosService::class);

$status = $service->getGenerationStatus($correlationId);

// Returns:
// - model_3d_id
// - model_status
// - texture_pack_uuid
// - texture_status
// - file_path (if completed)
// - progress (0-100)
```

### Retrying Failed Generations

```php
$textureService = app(TextureGenerationService::class);
$texturePack = $textureService->retryFailedGeneration($texturePackUuid);
```

### Cancelling Generation

```php
$service = app(Generate3DFromPhotosService::class);
$service->cancelGeneration($correlationId);
```

## Material Types

The system supports 15 material types with optimized LoRA configurations:

- **fabric**: General fabrics (cotton, polyester, blends)
- **leather**: Genuine leather and synthetic leather
- **denim**: Denim and jeans fabrics
- **knitwear**: Knitted fabrics, wool, sweaters
- **silk**: Silk, satin, and luxury fabrics
- **synthetic**: Technical and synthetic fabrics
- **wool**: Wool and fleece materials
- **linen**: Linen and natural fibers
- **velvet**: Velvet and plush fabrics
- **suede**: Suede and napped leather
- **canvas**: Canvas and heavy fabrics
- **technical**: Technical sportswear fabrics
- **mesh**: Mesh and perforated fabrics
- **rubber**: Rubber and synthetic materials
- **plastic**: Plastic and molded materials

## ControlNet Configuration

### Canny Edge Detection

- **Model**: `control_v11p_sd15_canny`
- **Weight**: 0.85
- **Purpose**: Preserves structure and edges

### Depth Map

- **Model**: `control_v11f1p_sd15_depth`
- **Weight**: 0.70
- **Purpose**: Adds volume and 3D structure

### OpenPose

- **Model**: `control_v11p_sd15_openpose`
- **Weight**: 0.70
- **Purpose**: Captures garment pose and shape

### Normal Map (Optional)

- **Model**: `control_v11p_sd15_normalbae`
- **Weight**: 0.65
- **Purpose**: Enhances surface details

## LoRA Configuration

### Material-Specific LoRA Weights

Each material type has optimized LoRA weights:

```json
{
  "fabric": ["FashionRealism_v2:0.78", "FabricTextile:0.72"],
  "leather": ["LeatherMaterials:0.82", "FashionRealism_v2:0.78", "GarmentConstruction:0.68"],
  "denim": ["FabricTextile:0.85", "FashionRealism_v2:0.78", "DenimSpecialized:0.75"]
}
```

### IP-Adapter Settings

- **Model**: `ip-adapter_sd15`
- **Weight**: 0.8
- **Purpose**: Preserves exact color and style from reference image

## Training Custom Fashion LoRA

### Dataset Requirements

**Minimum Requirements:**
- 800–1500 high-quality images
- Resolution: 1024×1024 or higher
- Product photography with studio lighting
- Clean white background
- Multiple angles (front, back, side, 3/4, details)

**Recommended Dataset Structure:**

```
datasets/catvrf_fashion/
├── images/
│   ├── 001_cotton_tshirt_front.jpg
│   ├── 001_cotton_tshirt_back.jpg
│   ├── 001_cotton_tshirt_detail.jpg
│   ├── 002_denim_jeans_front.jpg
│   └── ...
└── metadata.jsonl
```

**Caption Format:**

```json
{"file_name": "001_cotton_tshirt_front.jpg", "text": "masterpiece, best quality, product photography, white cotton t-shirt, detailed fabric weave, studio lighting, white background"}
```

### Training Parameters

**For Flux.1-dev:**
- Rank: 32
- Alpha: 16
- Batch Size: 2
- Gradient Accumulation: 4
- Learning Rate: 1e-4
- Max Steps: 5500
- Mixed Precision: bf16
- Resolution: 1024

**For SDXL:**
- Rank: 64–128
- Alpha: 32
- Batch Size: 4
- Learning Rate: 5e-5
- Max Steps: 8000–12000

### Training Command

```bash
python train_lora.py \
  --config config/fashion_lora_training.toml \
  --dataset_dir ./datasets/catvrf_fashion \
  --output_dir ./output/loras/catvrf_fashion
```

### Trigger Words

After training, use these trigger words:
- `catvrf fashion product`
- `studio product shot`
- `detailed clothing texture`

## Quality Metrics

The system tracks the following quality metrics:

- **Sharpness**: Edge clarity and detail preservation
- **Contrast**: Dynamic range of tonal values
- **Noise Level**: Amount of visual noise
- **Dynamic Range**: Utilization of available tonal range

### Quality Thresholds

- **Minimum Acceptable**: 0.6
- **High Quality**: 0.8+
- **Excellent**: 0.9+

## Monitoring and Analytics

### Livewire Dashboard

Access the texture generation monitor at:
`/admin/fashion/texture-generation-monitor`

Features:
- Real-time statistics
- Generation progress tracking
- Failed generation alerts
- Storage usage monitoring
- Material type distribution
- Daily statistics charts

### Statistics Available

- Total generations
- Pending/Processing/Completed/Failed counts
- Success rate
- Average generation time
- Material type distribution
- Daily generation trends

## Performance Optimization

### Generation Time

**Target:** 4–6 minutes per model

**Breakdown:**
- Mesh generation: 1–2 minutes
- Texture generation: 2–3 minutes
- Texture baking: 30–60 seconds
- Optimization: 30–60 seconds

### Optimization Tips

1. **Use Queue Processing**: Always process asynchronously
2. **Enable Caching**: Cache ControlNet preprocessed images
3. **Batch Processing**: Process multiple items when possible
4. **GPU Optimization**: Use mixed precision (bf16)
5. **Network Optimization**: Use CDN for texture delivery

## Troubleshooting

### Common Issues

**Issue:** Texture generation stuck in processing

**Solution:**
```bash
php artisan queue:restart
php artisan queue:work --queue=texture-generation
```

**Issue:** Low quality textures

**Solution:**
- Increase steps to 50–70
- Adjust CFG scale to 7.0–8.0
- Check LoRA weights
- Verify input photo quality

**Issue:** Memory errors

**Solution:**
- Reduce batch size
- Enable gradient checkpointing
- Use lower resolution (768x768)
- Enable cache_latents_to_disk

### Error Codes

- `ERR_001`: Stable Diffusion API unavailable
- `ERR_002`: Invalid photo format or resolution
- `ERR_003`: ControlNet model not found
- `ERR_004`: LoRA model not found
- `ERR_005`: Texture validation failed
- `ERR_006`: Mesh generation failed
- `ERR_007`: Texture baking failed
- `ERR_008**: Storage quota exceeded

## API Endpoints

### Generate Textures

```
POST /api/v1/fashion/textures/generate
Content-Type: application/json

{
  "model3d_id": 1,
  "product_name": "Premium Cotton T-Shirt",
  "material_type": "fabric",
  "photo_paths": ["photo1.jpg", "photo2.jpg", "photo3.jpg"]
}
```

### Check Status

```
GET /api/v1/fashion/textures/status/{correlation_id}
```

### Cancel Generation

```
POST /api/v1/fashion/textures/cancel/{correlation_id}
```

### Retry Failed

```
POST /api/v1/fashion/textures/retry/{texture_pack_uuid}
```

## Testing

Run the test suite:

```bash
php artisan test --testsuite=Feature,Fashion
```

Test coverage includes:
- Entity creation and transitions
- DTO validation
- Repository operations
- Service methods
- Quality metrics
- Error handling

## Security Considerations

1. **API Authentication**: All API endpoints require authentication
2. **Rate Limiting**: Implement rate limiting for generation requests
3. **Input Validation**: Validate all photo inputs
4. **Output Sanitization**: Sanitize generated textures
5. **Audit Logging**: Log all generation requests
6. **PII Protection**: No PII in external API calls

## Maintenance

### Daily Tasks

- Monitor queue health
- Check failed generations
- Review storage usage
- Monitor GPU utilization

### Weekly Tasks

- Review quality metrics
- Clean up old failed generations
- Update LoRA models if needed
- Review and update prompts

### Monthly Tasks

- Analyze generation statistics
- Optimize LoRA weights
- Update training dataset
- Review and update documentation

## Future Enhancements

- Multi-view consistency improvements
- Real-time texture preview
- Advanced material analysis
- Automated quality scoring
- Custom LoRA training pipeline
- Texture upscaling integration
- Material transfer learning
- AR/VR texture optimization

## Support

For issues and questions:
- Check the troubleshooting section
- Review logs in `storage/logs/laravel.log`
- Monitor queue status with Horizon
- Check GPU utilization with nvidia-smi

## References

- [Stable Diffusion Documentation](https://github.com/AUTOMATIC1111/stable-diffusion-webui)
- [ControlNet Documentation](https://github.com/lllyasviel/ControlNet-v1-1-nightly)
- [Flux.1 Model](https://blackforestlabs.ai/)
- [Kohya_ss Training Guide](https://github.com/kohya-ss/sd-scripts)

## Changelog

### v1.0.0 (2026-04-24)
- Initial implementation
- PBR texture generation with ControlNet
- Multi-material support
- LoRA integration
- Livewire monitoring dashboard
- Complete test suite
- Training configuration
- Comprehensive documentation
