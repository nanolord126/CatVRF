<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Payment\Models\PaymentTransaction;
use App\Domains\Payment\Models\PaymentInvoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Payment vertical...');

            for ($i = 1; $i <= 30; $i++) {
                PaymentInvoice::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'invoice_number' => "INV-{$i}",
                    'user_id' => rand(1, 10),
                    'amount' => rand(1000, 100000),
                    'due_date' => now()->addDays(rand(1, 30)),
                    'status' => ['pending', 'paid', 'overdue'][rand(0, 2)],
                ]);

                PaymentTransaction::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'transaction_id' => "TXN-{$i}",
                    'invoice_id' => $i,
                    'amount' => rand(1000, 100000),
                    'payment_method' => ['card', 'cash', 'transfer'][rand(0, 2)],
                    'status' => ['pending', 'completed', 'failed'][rand(0, 2)],
                    'processed_at' => now()->subDays(rand(1, 30)),
                ]);
            }

            $this->command->info('Payment vertical seeded successfully.');
        });
    }
}
