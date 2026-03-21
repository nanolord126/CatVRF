<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\CustomerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Аккаунты клиентов (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class CustomerAccountSeeder extends Seeder
{
    public function run(): void
    {
        CustomerAccount::factory()
            ->count(10)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}              [                  'first_name' => 'Иван',                  'last_name' => 'Петров',                  'email' => 'ivan.petrov@example.com',                  'phone' => '+7-999-123-45-67',                  'address' => 'Ул. Тверская, 1',                  'city' => 'Москва',                  'postal_code' => '101000',                  'preferred_payment' => 'card',                  'status' => 'active',                  'email_verified' => true,                  'phone_verified' => true,              ],              [                  'first_name' => 'Мария',                  'last_name' => 'Сидорова',                  'email' => 'maria.sidorova@example.com',                  'phone' => '+7-999-234-56-78',                  'address' => 'Ул. Пушкина, 50',                  'city' => 'Санкт-Петербург',                  'postal_code' => '190000',                  'preferred_payment' => 'wallet',                  'status' => 'active',                  'email_verified' => true,                  'phone_verified' => false,              ],          ];            foreach ($accounts as $account) {              CustomerAccount::create([                  ...$account,                  'correlation_id' => \Illuminate\Support\Str::uuid(),              ]);          }      }  }  