# Video Calling & Media System Setup Guide

This guide covers the implementation of video calling, media upload, optimization, and CDN integration for CatVRF.

## Table of Contents

1. [Video Calling System](#video-calling-system)
2. [Media Upload Service](#media-upload-service)
3. [Image Optimization](#image-optimization)
4. [CDN Integration](#cdn-integration)
5. [Video Optimization](#video-optimization)
6. [Configuration](#configuration)
7. [API Endpoints](#api-endpoints)
8. [Testing](#testing)

## Video Calling System

### Architecture

- **Signaling**: Laravel Reverb (WebSockets) + Laravel Echo
- **WebRTC**: LiveKit for peer-to-peer video/audio
- **SFU**: LiveKit or Mediasoup for group calls and streaming
- **Storage**: Tenant-isolated S3/MinIO for recordings

### Models

- `VideoRoom`: Represents a video call or live stream
- `VideoParticipant`: Tracks participants in a room
- `VideoRecording`: Stores processed video recordings

### Services

- `VideoRoomService`: Manages room creation, tokens, participants
- `RecordingService`: Handles recording storage and CDN sync
- `VideoOptimizationService`: FFmpeg-based video processing

### Events

- `VideoRoomStarted`: Broadcasted when a room starts
- `ParticipantJoined`: Broadcasted when a user joins
- `RecordingCompleted`: Broadcasted when processing finishes

### Setup Steps

1. **Install Dependencies**

```bash
composer require livekit/livekit-sdk
```

2. **Configure Environment**

Copy `.env.example.webrtc` to `.env` and configure:

```env
LIVEKIT_URL=ws://localhost:7880
LIVEKIT_API_KEY=your_api_key
LIVEKIT_API_SECRET=your_api_secret
VIDEO_RECORDING_DISK=s3
FFMPEG_PATH=/usr/bin/ffmpeg
```

3. **Run Migrations**

```bash
php artisan migrate
```

4. **Start LiveKit Server**

```bash
# Using Docker
docker run -d --name livekit -p 7880:7880 -p 7881:7881 -p 7882:7882/udp livekit/livekit-server
```

### Usage Example

```php
use App\Services\Video\VideoRoomService;

$videoRoomService = app(VideoRoomService::class);

// Create a video consultation room
$room = $videoRoomService->createRoom([
    'type' => 'consultation',
    'host_id' => $veterinarian->id,
    'host_type' => get_class($veterinarian),
    'pet_id' => $pet->id,
    'pet_type' => get_class($pet),
    'scheduled_at' => now()->addHours(1),
    'recording_enabled' => true,
]);

// Start the room
$videoRoomService->startRoom($room);

// Join as participant
$participant = $videoRoomService->joinRoom($room, $owner->id, get_class($owner));

// Get LiveKit token for WebRTC connection
$token = $videoRoomService->generateLiveKitToken($room, $participant);
```

## Media Upload Service

### Architecture

- **Spatie MediaLibrary**: Core media management
- **Tenant Isolation**: All files stored in `tenant/{tenant_id}/...`
- **Optimization**: Automatic WebP/AVIF conversion
- **Validation**: Centralized validation service

### Services

- `MediaUploadService`: Single entry point for uploads
- `MediaValidationService`: Validates files and import data
- `BulkImportService`: Handles bulk CSV/JSON imports

### Supported Operations

- Single image upload
- Multiple image upload
- Document upload (PDF, images)
- ZIP bulk upload
- CSV/JSON bulk import

### Setup Steps

1. **Spatie MediaLibrary is already installed** (in composer.json)

2. **Configure Storage**

```env
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=your_region
AWS_BUCKET=your_bucket
AWS_URL=https://your-bucket.s3.amazonaws.com
```

3. **Enable Optimizations** (optional)

```env
MEDIA_ENABLE_WEBP=true
MEDIA_ENABLE_AVIF=false
MEDIA_WEBP_QUALITY=82
```

### Usage Example

```php
use App\Services\Media\MediaUploadService;

$mediaService = app(MediaUploadService::class);

// Upload avatar
$media = $mediaService->uploadAvatar($user, $uploadedFile, 'avatar');

// Upload multiple images
$mediaCollection = $mediaService->uploadMultipleImages($product, $files, 'images');

// Bulk upload from ZIP
$mediaCollection = $mediaService->bulkUploadFromZip($product, $zipFile, 'images');
```

## Image Optimization

### Automatic Conversions

All uploaded images are automatically optimized:

- **WebP**: Primary format (82% quality)
- **AVIF**: Optional (if enabled, 78% quality)
- **Responsive**: Multiple sizes generated

### Conversion Profiles

| Conversion | Width | Height | Quality | Use Case |
|------------|-------|--------|---------|----------|
| avatar | 512 | 512 | 85 | User avatars |
| thumb | 300 | 300 | 80 | Thumbnails |
| medium | 1200 | 800 | 82 | Cards/previews |
| large | 2048 | 2048 | 78 | Full detail |
| medical | 1600 | 1200 | 92 | Medical records |

### CDN Integration

Images are served through CDN with automatic format selection:

```php
use App\Services\Media\CdnMediaService;

$cdnService = app(CdnMediaService::class);

$optimizedUrl = $cdnService->getOptimizedUrl($media, 'medium', [
    'format' => 'auto', // AVIF → WebP → JPEG
    'quality' => 82,
]);
```

## CDN Integration

### Supported Providers

- **Cloudflare Images**: Full optimization suite
- **Bunny CDN**: Fast, cost-effective option

### Configuration

Copy `.env.example.cdn` to `.env`:

```env
CDN_PROVIDER=cloudflare
CDN_ENABLED=true
CLOUDFLARE_ACCOUNT_ID=your_id
CLOUDFLARE_ACCOUNT_KEY=your_key
```

### Video CDN

For video streaming:

```env
CDN_VIDEO_PROVIDER=bunny_stream
CDN_VIDEO_ENABLED=true
BUNNY_STREAM_API_KEY=your_key
BUNNY_STREAM_LIBRARY_ID=your_library_id
```

### Usage

```php
use App\Traits\UsesCdnImages;

class Product extends Model implements HasMedia
{
    use HasMediaTrait;
    use UsesCdnImages;

    public function getOptimizedImageUrl()
    {
        return $this->getCdnUrl($this->getFirstMedia('images'), 'medium');
    }
}
```

## Video Optimization

### HLS Conversion

Recorded videos are automatically converted to HLS with adaptive bitrate:

- **360p**: 640x360 @ 800kbps
- **720p**: 1280x720 @ 2800kbps
- **1080p**: 1920x1080 @ 5000kbps

### Processing Pipeline

1. Raw recording stored to S3
2. `ProcessVideoJob` dispatched
3. FFmpeg extracts metadata
4. Thumbnail generated
5. HLS variants created
6. Master playlist generated
7. Synced to CDN (if enabled)

### Configuration

```env
VIDEO_ENABLE_HLS=true
VIDEO_PROCESSING_QUEUE=video-processing
VIDEO_PROCESSING_TIMEOUT=1800
```

## Configuration Files

### config/video.php

```php
return [
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),
    'livekit' => [
        'url' => env('LIVEKIT_URL'),
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
    ],
    // ... more options
];
```

### config/cdn.php

```php
return [
    'provider' => env('CDN_PROVIDER', 'cloudflare'),
    'enabled' => env('CDN_ENABLED', false),
    'video_provider' => env('CDN_VIDEO_PROVIDER', 'bunny_stream'),
    // ... more options
];
```

### config/media-library.php

```php
return [
    'disk_name' => env('MEDIA_DISK', 's3'),
    'image_optimizations' => [
        'enable_webp' => env('MEDIA_ENABLE_WEBP', true),
        'enable_avif' => env('MEDIA_ENABLE_AVIF', false),
    ],
    // ... more options
];
```

## API Endpoints

### Media Upload

#### Upload Single Image
```
POST /api/media/upload-image
Content-Type: multipart/form-data

{
    "file": <binary>,
    "collection": "images",
    "model_type": "App\\Models\\Product",
    "model_id": "uuid"
}
```

#### Upload Multiple Images
```
POST /api/media/upload-multiple
Content-Type: multipart/form-data

{
    "files": [<binary>, <binary>, ...],
    "collection": "images",
    "model_type": "App\\Models\\Product",
    "model_id": "uuid"
}
```

#### Bulk Upload ZIP
```
POST /api/media/bulk-upload-zip
Content-Type: multipart/form-data

{
    "zip_file": <binary>,
    "collection": "images",
    "model_type": "App\\Models\\Product",
    "model_id": "uuid"
}
```

#### Download Media
```
GET /api/media/download/{media_id}
```

#### Delete Media
```
DELETE /api/media/{media_id}
```

### Video Calling

#### Create Room
```
POST /api/video/rooms
{
    "type": "consultation",
    "host_id": "uuid",
    "host_type": "App\\Models\\User",
    "pet_id": "uuid",
    "pet_type": "App\\Models\\Pet",
    "scheduled_at": "2026-04-24T10:00:00Z",
    "recording_enabled": true
}
```

#### Start Room
```
POST /api/video/rooms/{room_id}/start
```

#### Join Room
```
POST /api/video/rooms/{room_id}/join
{
    "user_id": "uuid",
    "user_type": "App\\Models\\User"
}
```

#### Grant Recording Consent
```
POST /api/video/rooms/{room_id}/recording-consent
{
    "signature": "user_signature"
}
```

#### Download Recording
```
GET /api/video/recordings/{recording_id}/download
```

## Testing

### Run Tests

```bash
# Run all tests
./vendor/bin/pest

# Run video calling tests
./vendor/bin/pest tests/Feature/Video/

# Run media upload tests
./vendor/bin/pest tests/Feature/Media/
```

### Test Coverage

The system includes comprehensive tests for:
- Video room creation and management
- Participant joining/leaving
- Recording consent and storage
- Media upload and validation
- CDN URL generation
- Video optimization pipeline

### Example Test

```php
test('video room can be created with recording consent', function () {
    $room = VideoRoomService::createRoom([
        'type' => 'consultation',
        'host_id' => $veterinarian->id,
        'host_type' => get_class($veterinarian),
        'recording_enabled' => true,
    ]);

    expect($room->recording_enabled)->toBeTrue();
    expect($room->recording_consent_given)->toBeFalse();

    $room->giveRecordingConsent('digital_signature');
    
    expect($room->hasRecordingConsent())->toBeTrue();
});
```

## Security & Compliance

### Tenant Isolation

All media and video files are stored with tenant prefixes:
- `tenant/{tenant_id}/videos/{room_id}/...`
- `tenant/{tenant_id}/{model_type}/{model_id}/images/...`

### Recording Consent

Video recordings require explicit consent:
- Checkbox + digital signature
- Stored in database with timestamp
- Required before recording can start

### Data Protection

- PII anonymization before external AI processing
- Encrypted storage for sensitive data
- Audit logging for all operations
- 152-ФZ compliant

## Troubleshooting

### FFmpeg Not Found

```bash
# Install FFmpeg
sudo apt-get install ffmpeg

# Or update config
FFMPEG_PATH=/usr/local/bin/ffmpeg
```

### LiveKit Connection Issues

```bash
# Check LiveKit status
docker ps | grep livekit

# Check logs
docker logs livekit
```

### CDN Not Working

1. Verify API keys are correct
2. Check CDN provider status
3. Ensure `CDN_ENABLED=true` in .env
4. Check logs for sync errors

## Performance Tips

1. **Queue Configuration**: Use separate queues for video processing
2. **CDN Caching**: Set appropriate TTL (default 1 year)
3. **Image Optimization**: Enable WebP for 60-85% size reduction
4. **Video Quality**: Use 720p for most use cases, 1080p for premium
5. **Batch Processing**: Limit bulk imports to 50 items per batch

## Monitoring

### Key Metrics

- Video room creation rate
- Average call duration
- Recording processing time
- CDN sync success rate
- Storage usage per tenant

### Logs

All operations are logged with correlation IDs:
```php
Log::info('Video room created', [
    'room_id' => $room->id,
    'tenant_id' => $tenantId,
    'correlation_id' => $correlationId,
]);
```

## Support

For issues or questions:
1. Check the logs in `storage/logs/laravel.log`
2. Review configuration files
3. Check CDN provider status
4. Verify tenant isolation is working
