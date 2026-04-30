<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\PeerReviewRepositoryInterface;
use Modules\CatCRM\Domain\Staff\PeerReview;
use Modules\CatCRM\Domain\Staff\ValueObjects\PeerReviewId;
use Modules\CatCRM\Domain\Staff\ValueObjects\PeerReviewStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentPeerReviewRepository — Layer 7: Repository Implementation
 */
final class EloquentPeerReviewRepository implements PeerReviewRepositoryInterface
{
    public function findById(PeerReviewId $id): ?PeerReview
    {
        $record = DB::table('staff_peer_reviews')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByReviewer(int $tenantId, int $reviewerId): array
    {
        $records = DB::table('staff_peer_reviews')
            ->where('tenant_id', $tenantId)
            ->where('reviewer_id', $reviewerId)
            ->get()
            ->toArray();

        return array_map([$this, 'mapToEntity'], $records);
    }

    public function findByReviewee(int $tenantId, int $revieweeId): array
    {
        $records = DB::table('staff_peer_reviews')
            ->where('tenant_id', $tenantId)
            ->where
            ->toArray()('reviewee_id', $revieweeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findPendingByReviewer(int $tenantId, int $reviewerId): array
    {
        $records = DB::table('staff_peer_reviews')
            ->where('tenant_id', $tenantId)
            ->where
            ->toArray()('reviewer_id', $reviewerId)
            ->where('status', 'pending')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function save(PeerReview $peerReview): bool
    {
        $data = [
            'tenant_id' => $peerReview->tenantId,
            'reviewer_id' => $peerReview->reviewerId,
            'reviewee_id' => $peerReview->revieweeId,
            'rating' => $peerReview->rating,
            'feedback' => $peerReview->feedback,
            'strengths' => $peerReview->strengths,
            'areas_for_improvement' => $peerReview->areasForImprovement,
            'status' => $peerReview->status->value,
            'reviewed_at' => $peerReview->reviewedAt?->toDateTimeString(),
            'metadata' => json_encode($peerReview->metadata),
            'updated_at' => $peerReview->updatedAt->toDateTimeString(),
        ];

        if ($peerReview->id->value === 0) {
            $data['created_at'] = $peerReview->createdAt->toDateTimeString();
            $id = DB::table('staff_peer_reviews')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_peer_reviews')
                ->where('id', $peerReview->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(PeerReviewId $id): bool
    {
        return DB::table('staff_peer_reviews')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): PeerReview
    {
        return new PeerReview(
            id: PeerReviewId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            reviewerId: (int) $record['reviewer_id'],
            revieweeId: (int) $record['reviewee_id'],
            rating: (int) $record['rating'],
            feedback: $record['feedback'],
            strengths: $record['strengths'] ?? null,
            areasForImprovement: $record['areas_for_improvement'] ?? null,
            status: PeerReviewStatus::from($record['status']),
            reviewedAt: $record['reviewed_at'] ? CarbonImmutable::parse($record['reviewed_at']) : null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
