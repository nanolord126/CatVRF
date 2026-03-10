<?php

namespace Database\Seeders;

use App\Models\B2BPartner;
use App\Models\B2BContract;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class B2BSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name' => 'ООО "Вектор Плюс"',
                'inn' => '770112345678',
                'kpp' => '770101001',
                'legal_address' => 'г. Москва, ул. Арбат, д. 1',
                'email' => 'corp@vector-plus.ru',
            ],
            [
                'name' => 'Туристическое Агентство "Вояж"',
                'inn' => '780287654321',
                'email' => 'booking@voyage.travel',
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = B2BPartner::create(array_merge($partnerData, [
                'correlation_id' => (string) Str::uuid(),
            ]));

            $partner->createWallet([
                'name' => 'B2B Balance',
                'slug' => 'b2b-balance',
            ]);

            B2BContract::create([
                'partner_id' => $partner->id,
                'contract_number' => 'CTR-' . strtoupper(Str::random(6)),
                'start_date' => now()->subDays(30),
                'discount_percent' => 15.00,
                'credit_limit' => 500000.00,
                'payment_terms_days' => 14,
                'status' => 'active',
                'correlation_id' => (string) Str::uuid(),
            ]);
        }
    }
}
