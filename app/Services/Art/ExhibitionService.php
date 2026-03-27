<?php

declare(strict_types=1);

namespace App\Services\Art;

use App\Models\Art\ArtExhibition;
use App\Models\Art\ArtGallery;
use App\Services\FraudControlService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ExhibitionService — Management of gallery events and artwork showcasing.
 */
final readonly class ExhibitionService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private string $correlationId = ''
    ) {
        $this->correlationId = $correlationId ?: (Request()->header('X-Correlation-ID') ?? (string) Str::uuid());
    }

    /**
     * Create a new exhibition event for a gallery.
     * @throws \Exception
     */
    public function createExhibition(int $galleryId, array $data): ArtExhibition
    {
        $gallery = ArtGallery::findOrFail($galleryId);
        
        $this->fraud->check(['type' => 'exhibition_creation', 'gallery_id' => $galleryId, 'data' => $data]);

        return DB::transaction(function () use ($gallery, $data) {
            $exhibition = ArtExhibition::create(array_merge($data, [
                'gallery_id' => $gallery->id,
                'status' => 'scheduled',
                'correlation_id' => $this->correlationId,
                'slug' => Str::slug($data['title']),
            ]));

            Log::channel('audit')->info('Art exhibition created', [
                'id' => $exhibition->id,
                'title' => $exhibition->title,
                'correlation_id' => $this->correlationId,
            ]);

            return $exhibition;
        }, 5);
    }

    /**
     * Get ongoing or upcoming exhibitions.
     */
    public function getActiveExhibitions(): Collection
    {
        return ArtExhibition::where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['gallery', 'artworks'])
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Finalize an exhibition and archive it.
     */
    public function finishExhibition(int $exhibitionId): void
    {
        $exhibition = ArtExhibition::findOrFail($exhibitionId);

        DB::transaction(function () use ($exhibition) {
            $exhibition->update(['status' => 'archived']);
            
            Log::channel('audit')->warning('Exhibition finished', [
                'id' => $exhibitionId,
                'correlation_id' => $this->correlationId,
            ]);
        });
    }
}
