<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\Salon;
use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\BeautyService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BeautyAppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $businessGroupId = null;

        $salons = Salon::where('tenant_id', $tenantId)->get();
        $users = User::where('tenant_id', $tenantId)->limit(20)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping appointment seeder.');
            return;
        }

        $statuses = ['pending_payment', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];
        $cancellationReasons = ['Клиент отменил', 'Техническая ошибка', 'Мастер заболел', 'Переезд клиента'];

        foreach ($salons as $salon) {
            $masters = Master::where('salon_id', $salon->id)->get();
            $services = BeautyService::where('salon_id', $salon->id)->get();

            foreach ($masters as $master) {
                foreach ($services as $service) {
                    for ($i = 0; $i < 5; $i++) {
                        $user = $users->random();
                        $status = $statuses[array_rand($statuses)];
                        $isB2b = (bool) rand(0, 1);

                        $startsAt = now()->addDays(rand(-7, 14))->setHour(rand(9, 18))->setMinute(0);
                        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

                        $originalPrice = $service->price;
                        $dynamicPrice = $originalPrice * (0.8 + (rand(0, 40) / 100));
                        $flashDiscount = $isB2b ? $dynamicPrice * 0.15 : 0;
                        $finalPrice = max(0, $dynamicPrice - $flashDiscount);

                        $appointment = Appointment::create([
                            'tenant_id' => $tenantId,
                            'business_group_id' => $businessGroupId,
                            'salon_id' => $salon->id,
                            'master_id' => $master->id,
                            'service_id' => $service->id,
                            'user_id' => $user->id,
                            'uuid' => Str::uuid()->toString(),
                            'correlation_id' => Str::uuid()->toString(),
                            'status' => $status,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'total_price' => $finalPrice,
                            'is_b2b' => $isB2b,
                            'cancellation_reason' => $status === 'cancelled' ? $cancellationReasons[array_rand($cancellationReasons)] : null,
                            'tags' => json_encode(['запись'], JSON_THROW_ON_ERROR),
                            'metadata' => json_encode([
                                'original_price' => $originalPrice,
                                'dynamic_price' => $dynamicPrice,
                                'flash_discount' => $flashDiscount,
                                'commission_rate' => $isB2b ? 0.12 : 0.14,
                                'commission_amount' => $finalPrice * ($isB2b ? 0.12 : 0.14),
                                'payment_method' => rand(0, 1) ? 'wallet' : 'card',
                                'created_via' => 'seeder',
                                'rating' => $status === 'completed' ? rand(30, 50) / 10 : null,
                            ], JSON_THROW_ON_ERROR),
                        ]);

                        if ($status === 'confirmed' || $status === 'completed') {
                            DB::table('balance_transactions')->insert([
                                'wallet_id' => $this->getOrCreateWallet($user->id, $tenantId),
                                'type' => 'debit',
                                'amount' => (int) ($finalPrice * 100),
                                'status' => 'completed',
                                'reason' => 'beauty_appointment_payment',
                                'correlation_id' => $appointment->correlation_id,
                                'metadata' => json_encode(['appointment_id' => $appointment->id], JSON_THROW_ON_ERROR),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        $this->command->info('Beauty Appointment Seeder completed successfully.');
    }

    private function getOrCreateWallet(int $userId, int $tenantId): int
    {
        $wallet = DB::table('wallets')->where('user_id', $userId)->first();

        if (!$wallet) {
            $walletId = DB::table('wallets')->insertGetId([
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'current_balance' => rand(50000, 200000),
                'hold_amount' => 0,
                'correlation_id' => Str::uuid()->toString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $walletId;
        }

        return $wallet->id;
    }
}
