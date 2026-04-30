<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supermarket_documents', function (Blueprint $table) {
            // Payment delay fields
            $table->integer('payment_delay_days')->default(0)->after('temperature_compliance_status');
            $table->timestamp('payment_delay_until')->nullable()->after('payment_delay_days');
            
            // Add index for payment delay queries
            $table->index('payment_delay_until');
        });
    }

    public function down(): void
    {
        Schema::table('supermarket_documents', function (Blueprint $table) {
            $table->dropIndex(['payment_delay_until']);
            $table->dropColumn([
                'payment_delay_days',
                'payment_delay_until',
            ]);
        });
    }
};
