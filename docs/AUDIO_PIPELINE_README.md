# Audio Processing Pipeline with CDN Integration

A professional, secure, and cost-effective audio processing system for CatVRF with full CDN integration.

## Overview

The audio pipeline handles:
- Video call recordings (audio track extraction)
- Voice-only consultations
- Audio reports after appointments (veterinarian dictation)
- Educational audio for groomers and veterinarians
- Voice reminders for pet owners (Telegram bot)
- Consultation recordings for medical records

## Architecture

```
┌─────────────────┐
│  Audio Capture  │ (LiveKit / WebRTC / Upload)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Raw Storage    │ (S3/MinIO - tenant-isolated)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ProcessAudioJob │ (async queue)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ FFmpeg Optimize │ (normalize, compress, denoise)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  CDN Upload     │ (Bunny / Cloudflare / AWS)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Secure URL     │ (signed, TTL-based)
└─────────────────┘
```

## Technology Stack

- **Storage**: S3/MinIO (tenant-isolated buckets)
- **Processing**: FFmpeg (normalization, compression, noise reduction)
- **CDN**: Bunny.net Stream / Cloudflare Stream / AWS CloudFront
- **Formats**: Opus (best quality/size), AAC (compatibility), MP3 (fallback)
- **Delivery**: HLS for audio (m4a segments) + direct MP3/AAC fallback

## Configuration

### Environment Variables

```bash
# .env
AUDIO_CDN_PROVIDER=bunny

# Bunny CDN
BUNNY_API_KEY=your_api_key
BUNNY_STORAGE_ZONE=catvrf-audio
BUNNY_AUDIO_HOSTNAME=catvrf-audio.b-cdn.net

# Cloudflare Stream (alternative)
CLOUDFLARE_ACCOUNT_ID=your_account_id
CLOUDFLARE_API_TOKEN=your_api_token

# AWS CloudFront (alternative)
AWS_CLOUDFRONT_DOMAIN=d123.cloudfront.net
AWS_S3_AUDIO_BUCKET=catvrf-audio

# Audio Processing
AUDIO_DEFAULT_FORMAT=opus
AUDIO_OPUS_BITRATE=64k
AUDIO_AAC_BITRATE=128k
AUDIO_SAMPLE_RATE=48000
AUDIO_LOUDNORM_TARGET=I=-16:TP=-1.5:LRA=11

# Queue
AUDIO_QUEUE_NAME=audio-processing
AUDIO_QUEUE_CONNECTION=redis
AUDIO_QUEUE_MEMORY_LIMIT=512
```

### Queue Configuration

Add to `config/horizon.php`:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['audio-processing'],
            'balance' => 'auto',
            'processes' => 3,
            'memory' => 512, // MB
            'timeout' => 300, // 5 minutes
        ],
    ],
],
```

## Usage

### Adding the Trait

```php
use App\Traits\HasOptimizedAudio;

class Appointment extends Model
{
    use HasOptimizedAudio;
}
```

### Uploading Audio Reports

```php
// Veterinarian uploads audio report
$appointment->addAudioReport(
    $uploadedFile,
    'audio_reports'
);
```

### Processing Video Recordings

```php
use App\Services\Audio\AudioOptimizationService;

$service = app(AudioOptimizationService::class);

// Process after video call ends
$service->processRecording($videoRecording);
```

### Getting Secure CDN URLs

```php
$url = $appointment->getOptimizedAudioUrl($media, 1440); // 24 hours

// For player
$playerUrl = $appointment->getAudioPlayerUrl($media);
```

### Audio Metadata

```php
$metadata = $appointment->getAudioMetadata($media);
// Returns: duration, bitrate, sample_rate, channels, codec
```

## FFmpeg Optimization

The pipeline automatically applies:

1. **Video Removal** (`-vn`) - Extract audio only
2. **Codec Selection** - Opus for quality/size, AAC for compatibility
3. **Bitrate Optimization** - 64k (Opus) or 128k (AAC)
4. **Sample Rate** - 48kHz (broadcast quality)
5. **Loudness Normalization** - EBU R128 standard (`I=-16:TP=-1.5:LRA=11`)

### FFmpeg Command Example

```bash
ffmpeg -i input.wav \
  -vn \
  -c:a libopus \
  -b:a 64k \
  -ar 48000 \
  -af loudnorm=I=-16:TP=-1.5:LRA=11 \
  -y output.opus
```

## CDN Integration

### Bunny CDN

```php
// Automatic upload after optimization
// Signed URL generation
$url = $service->getSecureCdnUrl($media, 1440);
```

### Cloudflare Stream

```php
// Uploads to Cloudflare Stream
// Returns HLS playback URL
// Requires account_id and API token
```

### AWS CloudFront + S3

```php
// Uses Media Library's S3 disk
// CloudFront signed URLs
// Private content delivery
```

## Queue Processing

### ProcessAudioJob

The job handles:
- Video → Audio extraction
- Audio optimization
- CDN upload
- Error handling and retry
- Status updates

```php
// Dispatch
ProcessAudioJob::dispatch($media, 'opus', true)
    ->onQueue('audio-processing');
```

### Monitoring

Monitor queue health in Horizon:
- Job processing time
- Failure rate
- Queue depth
- Memory usage

## Security

### Tenant Isolation

All audio files stored in tenant-isolated buckets:
```
s3://catvrf-audio/tenant_{id}/appointments/{appointment_id}/audio.opus
```

### Signed URLs

All CDN URLs are signed with TTL:
- Default: 24 hours
- Maximum: 7 days
- Configurable per request

```php
$url = $service->getSecureCdnUrl($media, 60); // 1 hour
```

### Consent Recording

All audio recordings require explicit consent:
```php
$appointment->update([
    'audio_consent_given' => true,
    'audio_consent_at' => now(),
    'audio_consent_method' => 'checkbox',
]);
```

### Audit Logging

All audio operations are logged:
- Upload
- Processing start/end
- CDN upload
- URL generation
- Playback access

## Performance

### Optimization Targets

- **Compression**: 70-90% size reduction
- **Quality**: Transparent audio (perceptually lossless)
- **Processing Time**: < 30 seconds for 10-minute recording
- **CDN Delivery**: < 100ms global latency

### Best Practices

1. **Async Processing**: Always use queue for FFmpeg operations
2. **CDN First**: Always serve from CDN when available
3. **Format Selection**: Use Opus for new recordings, AAC for compatibility
4. **TTL Management**: Use appropriate URL expiration
5. **Cleanup**: Remove temp files after processing

## Integration Examples

### Video Call Recording

```php
// After LiveKit recording ends
$recording = $call->recordings()->create([
    'path' => $recordingPath,
    'duration' => $duration,
]);

$service->processRecording($recording);
```

### Voice Consultation

```php
// Upload voice-only consultation
$media = $consultation->addVoiceRecording($audioFile);

// Get URL for playback
$url = $consultation->getAudioPlayerUrl($media);
```

### Audio Report

```php
// Veterinarian dictates conclusion
$report = $appointment->addAudioReport($audioFile);

// Store in medical record
$medicalRecord->audio_report_id = $report->id;
```

### Telegram Bot Voice Message

```php
// Send voice reminder
$audioUrl = $reminder->getOptimizedAudioUrl($media, 10080); // 7 days

Telegram::sendAudio([
    'chat_id' => $user->telegram_chat_id,
    'audio' => $audioUrl,
    'caption' => 'Reminder: Your appointment is tomorrow',
]);
```

## Testing

```bash
# Run audio service tests
php artisan test --filter=AudioOptimizationServiceTest

# Run job tests
php artisan test --filter=ProcessAudioJobTest
```

**Note**: FFmpeg tests are skipped in CI without FFmpeg installed.

## Troubleshooting

### FFmpeg Not Found

```bash
# Install FFmpeg
# Ubuntu/Debian
sudo apt-get install ffmpeg

# macOS
brew install ffmpeg

# Windows
# Download from https://ffmpeg.org/download.html
```

### Queue Processing Slow

1. Increase queue workers
2. Increase memory limit
3. Check FFmpeg performance
4. Monitor disk I/O

### CDN Upload Fails

1. Check API credentials
2. Verify storage zone/bucket exists
3. Check network connectivity
4. Review CDN logs

### Audio Quality Issues

1. Adjust bitrate settings
2. Check source quality
3. Verify loudnorm settings
4. Test different codecs

## Monitoring & Alerts

### Filament Dashboard

Create dashboard for:
- Audio processing statistics
- Size before/after optimization
- Processing time trends
- Error rates
- CDN usage

### Alerts

Set up alerts for:
- Processing time > 3 minutes
- Error rate > 5%
- Queue depth > 100 jobs
- CDN upload failures

## Future Enhancements

- [ ] Real-time audio streaming
- [ ] Speech-to-text (medical transcription)
- [ ] Audio compression AI models
- [ ] Multi-language support
- [ ] Audio watermarking for compliance
- [ ] Real-time audio analysis (stress detection in pets)

## Compliance

This pipeline is designed for medical compliance:
- **152-ФЗ**: Data stored in Russian data centers (when required)
- **FZ-323**: Medical data handling compliance
- **GDPR**: Data minimization and consent
- **HIPAA**: PHI protection (for international clients)

All audio files are:
- Tenant-isolated
- Access-controlled via signed URLs
- Audited for access
- Stored with consent records
- Never sent to external LLMs without anonymization
