<?php

namespace App\Traits\Common;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Domains\Common\Services\AI\ContentShieldService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

trait HasEcosystemMedia
{
    use InteractsWithMedia;

    /**
     * AI Анализ и блокировка некачественного или запрещенного контента.
     */
    public function validateAndUpload(UploadedFile $file, string $collection = 'gallery'): Media
    {
        $shield = app(ContentShieldService::class);
        $analysis = $shield->analyzeUpload($file);

        if (!$analysis['is_allowed']) {
            throw new \Exception("Upload Blocked by AI Shield: " . $analysis['reason']);
        }

        return $this->addMedia($file)
            ->withCustomProperties([
                'quality_score' => $analysis['quality_score'],
                'ai_audit_timestamp' => Carbon::now()->toDateTimeString(),
                'ocr_text' => $analysis['ocr_text']
            ])
            ->toMediaCollection($collection);
    }

    /**
     * Стандартные коллекции для всех объектов экосистемы.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useFallbackUrl('/assets/images/default-avatar.png')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150);
            });

        $this->addMediaCollection('gallery')
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('preview')
                    ->width(800)
                    ->height(600);
            });

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->useDisk('private'); // Хранение документов в защищенном диске
    }
}
