<?php declare(strict_types=1);

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductEmbedding extends Model
{
    use HasFactory, TenantScoped;

        protected $table = 'product_embeddings';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'embeddable_type',
            'embeddable_id',
            'embedding',
            'source_text',
            'model_version',
            'product_metadata',
        ];

        protected $casts = [
            'embedding' => 'json',
            'product_metadata' => 'json',
        ];

        public $timestamps = false;

        // ============ Global Scopes ============

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                $query->where('product_embeddings.tenant_id', tenant()->id);
            });
        }

        // ============ Methods ============

        /**
         * Получить embedding как array (для cosine similarity)
         */
        public function getEmbeddingArray(): array
        {
            $embedding = $this->embedding;

            if (\is_string($embedding)) {
                return \json_decode($embedding, true) ?? [];
            }

            return $embedding ?? [];
        }

        /**
         * Вычислить cosine similarity с другим embeddings
         *
         * Cosine Similarity = (A · B) / (||A|| * ||B||)
         */
        public function cosineSimilarity(array $other): float
        {
            $a = $this->getEmbeddingArray();

            if (empty($a) || empty($other)) {
                return 0.0;
            }

            // Скалярное произведение
            $dot = 0;
            foreach ($a as $i => $val) {
                $dot += ($val * ($other[$i] ?? 0));
            }

            // Нормы
            $normA = \sqrt(\array_sum(\array_map(fn ($x) => $x ** 2, $a)));
            $normB = \sqrt(\array_sum(\array_map(fn ($x) => $x ** 2, $other)));

            if ($normA == 0 || $normB == 0) {
                return 0.0;
            }

            return (float)($dot / ($normA * $normB));
        }
}
