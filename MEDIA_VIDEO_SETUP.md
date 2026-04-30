# Media & Video System Setup Guide

## Overview

CatVRF implements a comprehensive media and video system with:
- **Centralized file upload** with CDN integration (Cloudflare Images / Bunny CDN)
- **Video calls and live streaming** via LiveKit WebRTC
- **Adaptive bitrate streaming** with HLS
- **Tenant-isolated storage** for multi-tenancy compliance
- **Medical compliance** (152-ФЗ, ФЗ-323) with PII anonymization

## Architecture

### Media Module

```
modules/Media/
├── Domain/
│   ├── Entities/
│   │   └── MediaFile.php
│   ├── ValueObjects/
│   │   ├── MediaCollectionType.php
│   │   ├── MediaMimeType.php
│   │   └── MediaStatus.php
│   ├── DTOs/
│   │   ├── UploadMediaDTO.php
│   │   ├── BulkImportDTO.php
│   │   └── MediaValidationResultDTO.php
│   ├── Exceptions/
│   │   ├── MediaUploadException.php
│   │   └── BulkImportException.php
│   └── Repositories/
│       └── MediaRepositoryInterface.php
├── Application/
│   ├── Services/
│   │   ├── MediaUploadService.php
│   │   ├── MediaValidationService.php
│   │   ├── BulkImportService.php
│   │   └── CdnMediaService.php
│   ├── Jobs/
│   │   ├── ProcessImageConversionsJob.php
│   │   └── ProcessVideoConversionsJob.php
│   └── Livewire/
│       ├── MediaUpload.php
│       └── BulkImport.php
├── Infrastructure/
│   ├── Models/
│   │   └── MediaFileModel.php
│   ├── Repositories/
│   │   └── EloquentMediaRepository.php
│   └── Providers/
│       └── MediaServiceProvider.php
└── Presentation/
    ├── Http/Controllers/
    │   └── MediaController.php
    └── Routes/
        └── api.php
```

### Video Module

```
modules/Video/
├── Domain/
│   ├── Entities/
│   │   ├── VideoRoom.php
│   │   └── VideoParticipant.php
│   ├── ValueObjects/
│   │   ├── RoomType.php
│   │   ├── RoomStatus.php
│   │   ├── ParticipantRole.php
│   │   └── ParticipantStatus.php
│   ├── DTOs/
│   │   ├── CreateRoomDTO.php
│   │   └── JoinRoomDTO.php
│   ├── Exceptions/
│   │   └── VideoRoomException.php
│   └── Repositories/
│       └── VideoRoomRepositoryInterface.php
├── Application/
│   ├── Services/
│   │   ├── VideoRoomService.php
│   │   ├── LiveKitService.php
│   │   └── VideoOptimizationService.php
│   ├── Jobs/
│   │   └── ProcessVideoRecordingJob.php
│   └── Livewire/
│       └── VideoCall.php
├── Infrastructure/
│   ├── Models/
│   │   ├── VideoRoomModel.php
│   │   └── VideoParticipantModel.php
│   ├── Repositories/
│   │   └── EloquentVideoRoomRepository.php
│   └── Providers/
│       └── VideoServiceProvider.php
└── Presentation/
    ├── Http/Controllers/
    │   └── VideoRoomController.php
    └── Routes/
        └── api.php
```

## Installation

### 1. Composer Dependencies

```bash
# Required packages (install after PHP 8.3 upgrade)
composer require livewire/livewire:^3.0
composer require laravel/reverb
composer require intervention/image:^3.0
composer require spatie/image-optimizer
composer require php-ffmpeg/php-ffmpeg
composer require maatwebsite/excel:^3.1
```

### 2. Environment Configuration

Add to `.env`:

```env
# CDN Configuration
CDN_PROVIDER=cloudflare
CDN_ENABLED=false
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_IMAGES_API_KEY=your_api_key
CLOUDFLARE_IMAGES_ENABLED=false
BUNNY_STORAGE_ZONE_NAME=your_zone
BUNNY_ACCESS_KEY=your_key
CDN_AUTO_UPLOAD=false

# Video Configuration
LIVEKIT_ENABLED=true
LIVEKIT_HOST_URL=ws://localhost:7880
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_secret
FFMPEG_PATH=/usr/bin/ffmpeg
VIDEO_RECORDING_ENABLED=true
VIDEO_RECORDING_REQUIRE_CONSENT=true
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates:
- `media_files` table
- `video_rooms` table
- `video_participants` table

### 4. Service Providers

Already registered in `config/app.php`:
- `Modules\Media\Infrastructure\Providers\MediaServiceProvider::class`
- `Modules\Video\Infrastructure\Providers\VideoServiceProvider::class`

## Usage

### Media Upload

#### Single File Upload

```php
use Modules\Media\Domain\Traits\HasMediaTrait;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;

class Pet extends Model
{
    use HasMediaTrait;
}

// In controller
$pet = Pet::find(1);
$mediaFile = $pet->addAvatar($request->file('avatar'));
```

#### Multiple Files Upload

```php
$mediaFiles = $uploadService->uploadMultiple($files, $dto);
```

#### Bulk Import

```php
$bulkImportService = app(BulkImportService::class);
$result = $bulkImportService->importFromZip($zipFile, 'vetgrooming');
```

### Video Calls

#### Create Video Room

```php
use Modules\Video\Application\Services\VideoRoomService;
use Modules\Video\Domain\DTOs\CreateRoomDTO;
use Modules\Video\Domain\ValueObjects\RoomType;

$roomService = app(VideoRoomService::class);

$dto = new CreateRoomDTO(
    type: RoomType::CONSULTATION,
    hostId: (string) Auth::id(),
    petId: 'pet-uuid',
    requireRecordingConsent: true,
);

$room = $roomService->createRoom($dto);
```

#### Join Video Room

```php
use Modules\Video\Domain\DTOs\JoinRoomDTO;
use Modules\Video\Domain\ValueObjects\ParticipantRole;

$dto = new JoinRoomDTO(
    roomId: $room->id,
    userId: (string) Auth::id(),
    role: ParticipantRole::VIEWER,
);

$participant = $roomService->joinRoom($dto);
$accessToken = $roomService->getParticipantAccessToken($room->id, Auth::id());
```

#### Livewire Component

```blade
<livewire:video-call :roomId="$room->id" />
```

### CDN Integration

All file operations go through CDN:

```php
$cdnService = app(CdnMediaService::class);

// Get optimized URL
$url = $cdnService->getOptimizedUrl($mediaFile, 'medium');

// Upload to CDN
$cdnService->uploadToCdn($mediaFile);

// Get signed URL
$url = $cdnService->getSignedUrl($mediaFile, 15); // 15 minutes TTL
```

## API Endpoints

### Media API

- `POST /api/v1/media/upload` - Upload single file
- `POST /api/v1/media/upload-multiple` - Upload multiple files
- `POST /api/v1/media/bulk-import` - Bulk import from JSON/ZIP
- `GET /api/v1/media/{mediaId}/optimized` - Get optimized URL
- `GET /api/v1/media/{mediaId}/download` - Get download URL
- `DELETE /api/v1/media/{mediaId}` - Delete media
- `GET /api/v1/media/by-model` - Get media by model

### Video API

- `POST /api/v1/video/rooms` - Create video room
- `POST /api/v1/video/rooms/{roomId}/start` - Start room
- `POST /api/v1/video/rooms/{roomId}/end` - End room
- `POST /api/v1/video/rooms/{roomId}/join` - Join room
- `POST /api/v1/video/rooms/{roomId}/leave/{userId}` - Leave room
- `POST /api/v1/video/rooms/{roomId}/consent` - Give recording consent
- `GET /api/v1/video/rooms/{roomId}/access-token/{userId}` - Get access token
- `GET /api/v1/video/rooms/{roomId}` - Get room details
- `GET /api/v1/video/rooms/by-host/{hostId}` - Get rooms by host

## Integration with Verticals

### VetGrooming

Add `HasMediaTrait` to models:

```php
namespace Modules\VetGrooming\Domain\Entities;

use Modules\Media\Domain\Traits\HasMediaTrait;

class Pet
{
    use HasMediaTrait;

    // Add pet photos, medical documents, etc.
}

class GroomingSession
{
    use HasMediaTrait;

    // Add before/after photos
}
```

### BeautyMasters

```php
namespace Modules\BeautyMasters\Domain\Entities;

use Modules\Media\Domain\Traits\HasMediaTrait;

class Master
{
    use HasMediaTrait;

    // Add portfolio photos, avatar
}

class Service
{
    use HasMediaTrait;

    // Add service photos
}
```

## Video Room Types

| Type | Description | Max Participants | Recording Consent |
|------|-------------|------------------|-------------------|
| `consultation` | 1:1 vet consultation | 2 | Required |
| `grooming_demo` | Live grooming demo | 100 | Optional |
| `masterclass` | Training masterclass | 500 | Optional |
| `surgery` | Surgery broadcast | 10 | Required |
| `group_call` | Multi-specialist call | 10 | Optional |

## Media Collections

| Collection | Description | Max Size |
|------------|-------------|----------|
| `avatar` | User/business avatars | 10MB |
| `images` | General images | 10MB |
| `documents` | PDF documents | 15MB |
| `medical` | Medical photos | 15MB |
| `before_after` | Before/after photos | 15MB |
| `portfolio` | Portfolio items | 10MB |
| `products` | Product photos | 10MB |
| `services` | Service photos | 10MB |
| `video_recording` | Video recordings | 500MB |
| `video_thumbnail` | Video thumbnails | 10MB |

## Security & Compliance

### Tenant Isolation

All files are stored with tenant prefix:
```
tenant/{tenant_id}/media/{model_type}/{model_id}/{uuid}.ext
```

### Medical Compliance

- PII anonymization before external AI processing
- Recording consent required for consultations
- Audit logging for all media operations
- Encrypted storage for sensitive documents

### CDN Security

- Signed URLs with TTL (default 15 minutes)
- Tenant-scoped CDN paths
- Automatic fallback to local storage

## Queues

Configure queues in `.env`:

```env
QUEUE_CONNECTION=redis

# Media processing queues
MEDIA_OPTIMIZATION_QUEUE=media-optimization

# Video processing queues
VIDEO_PROCESSING_QUEUE=video-processing
```

## Performance

### CDN Optimization

- WebP/AVIF automatic conversion
- Responsive image generation
- Adaptive bitrate streaming for video
- Edge caching with 1-year TTL

### Caching

Media URLs are cached with tenant tags:
```php
Cache::tags(['media', 'user:' . $userId])->remember(...)
```

## Troubleshooting

### CDN Upload Fails

Check CDN credentials in `.env`:
```bash
php artisan config:clear
php artisan cache:clear
```

### Video Processing Stuck

Check queue worker:
```bash
php artisan queue:work --queue=video-processing
```

### FFmpeg Not Found

Ensure FFmpeg is installed:
```bash
ffmpeg -version
```

Update path in `.env`:
```env
FFMPEG_PATH=/usr/local/bin/ffmpeg
```

## Testing

Run tests:
```bash
php artisan test --filter=MediaTest
php artisan test --filter=VideoTest
```

## Monitoring

### ClickHouse Metrics

Media operations are logged to ClickHouse:
- Upload success/failure rates
- CDN upload latency
- Video processing duration
- Storage usage per tenant

### Prometheus Metrics

Expose metrics at `/metrics`:
- `catvrf_media_uploads_total`
- `catvrf_cdn_upload_duration_seconds`
- `catvrf_video_processing_duration_seconds`

## Future Enhancements

- [ ] WebP/AVIF on-the-fly conversion
- [ ] AI-powered image categorization
- [ ] Video transcoding with GPU acceleration
- [ ] Real-time video analysis
- [ ] Automated quality scoring
- [ ] Storage cost optimization
