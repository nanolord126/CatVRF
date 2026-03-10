<?php

namespace Database\Seeders;

use App\Domains\Finances\Models\PaymentTransaction;
use App\Domains\Finances\Models\RecurringModels;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FinancesSeeder extends Seeder
{
    /**
     * Заполнение БД тестовыми данными для финансового модуля.
     */
    public function run(): void
    {
        // Получить пользователей для тестирования
        $users = User::limit(5)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Skipping FinancesSeeder.');
            return;
        }

        // Создать платёжные транзакции
        foreach ($users as $user) {
            PaymentTransaction::factory()
                ->count(3)
                ->for($user)
                ->create([
                    'tenant_id' => $user->current_tenant_id,
                    'correlation_id' => Str::uuid(),
                ]);
        }

        $this->command->info('Payment transactions created successfully.');

        // Создать сохранённые карты
        $walletCards = [];
        foreach ($users as $user) {
            for ($i = 0; $i < 2; $i++) {
                $walletCards[] = RecurringModels\WalletCard::create([
                    'user_id' => $user->id,
                    'tenant_id' => $user->current_tenant_id,
                    'token' => 'tok_' . Str::random(20),
                    'card_last_four' => str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'card_brand' => $this->getRandomCardBrand(),
                    'exp_month' => rand(1, 12),
                    'exp_year' => now()->year + rand(1, 3),
                    'is_active' => true,
                    'is_default' => $i === 0,
                    'correlation_id' => Str::uuid(),
                ]);
            }
        }

        $this->command->info('Wallet cards created successfully.');

        // Создать повторяющиеся подписки
        foreach ($walletCards as $card) {
            RecurringModels\Subscription::create([
                'user_id' => $card->user_id,
                'tenant_id' => $card->tenant_id,
                'wallet_card_id' => $card->id,
                'amount' => rand(500, 5000) / 100,
                'frequency' => $this->getRandomFrequency(),
                'status' => RecurringModels\Subscription::STATUS_ACTIVE,
                'starts_at' => now()->subDays(rand(1, 30)),
                'ends_at' => null,
                'last_payment_at' => now()->subDays(rand(1, 30)),
                'next_payment_at' => now()->addDays(rand(1, 30)),
                'correlation_id' => Str::uuid(),
                'metadata' => [
                    'source' => 'seeder',
                    'description' => 'Premium subscription',
                ],
            ]);
        }

        $this->command->info('Subscriptions created successfully.');
    }

    /**
     * Получить случайный бренд карты.
     */
    private function getRandomCardBrand(): string
    {
        return collect(['VISA', 'MASTERCARD', 'MIR', 'AMEX'])->random();
    }

    /**
     * Получить случайную периодичность подписки.
     */
    private function getRandomFrequency(): string
    {
        return collect([
            RecurringModels\Subscription::FREQUENCY_DAILY,
            RecurringModels\Subscription::FREQUENCY_WEEKLY,
            RecurringModels\Subscription::FREQUENCY_MONTHLY,
            RecurringModels\Subscription::FREQUENCY_YEARLY,
        ])->random();
    }
}
