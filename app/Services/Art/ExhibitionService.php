<?php declare(strict_types=1);

namespace App\Services\Art;


use Illuminate\Http\Request;
use App\Services\FraudControlService;
use App\Models\Art\ArtGallery;
use App\Models\Art\ArtExhibition;
use Illuminate\Database\Eloquent\Collection;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

final readonly class ExhibitionService
{

    public function __construct(
        private readonly Request $request,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
    ) {}

        private function correlationId(): string
        {
            return $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        }

        /**
         * Create a new exhibition event for a gallery.
         * @throws \Exception
         */
        public function createExhibition(int $galleryId, array $data): ArtExhibition
        {
            $gallery = ArtGallery::findOrFail($galleryId);

            $this->fraud->check((int) $this->guard->id(), 'exhibition_creation', $this->request->ip());

            return $this->db->transaction(function () use ($gallery, $data) {
                $exhibition = ArtExhibition::create(array_merge($data, [
                    'gallery_id' => $gallery->id,
                    'status' => 'scheduled',
                    'correlation_id' => $this->correlationId(),
                    'slug' => Str::slug($data['title']),
                ]));

                $this->logger->channel('audit')->info('Art exhibition created', [
                    'id' => $exhibition->id,
                    'title' => $exhibition->title,
                    'correlation_id' => $this->correlationId(),
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

            $this->db->transaction(function () use ($exhibition) {
                $exhibition->update(['status' => 'archived']);

                $this->logger->channel('audit')->warning('Exhibition finished', [
                    'id' => $exhibitionId,
                    'correlation_id' => $this->correlationId,
                ]);
            });
        }
}
