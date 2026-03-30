<?php declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyTryOnService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly FraudControlService $fraudControlService,)
        {
        }

        public function logTryOn(int $productId, int $userId, bool $purchased, string $correlationId): bool
        {


            try {
                            $this->fraudControlService->check(
                    auth()->id() ?? 0,
                    __CLASS__ . '::' . __FUNCTION__,
                    0,
                    request()->ip(),
                    null,
                    $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
                );
                DB::transaction(function () use ($productId, $userId, $purchased, $correlationId) {
                    DB::table('cosmetic_tryons')->insert([
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'purchased' => $purchased,
                        'correlation_id' => $correlationId,
                        'created_at' => now(),
                    ]);

                    Log::channel('audit')->info('Cosmetic try-on logged', [
                        'product_id' => $productId,
                        'user_id' => $userId,
                        'purchased' => $purchased,
                        'correlation_id' => $correlationId,
                    ]);
                });

                return true;
            } catch (\Exception $e) {
                Log::channel('audit')->error('Try-on logging failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
}
