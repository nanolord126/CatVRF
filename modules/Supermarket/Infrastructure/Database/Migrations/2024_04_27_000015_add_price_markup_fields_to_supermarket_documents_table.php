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
            // Price markup fields for payment delays
            $table->decimal('price_with_delay', 10, 3)->nullable()->after('payment_delay_until');
            $table->decimal('price_markup_percent', 5, 2)->default(0)->after('price_with_delay');
            
            // Add index for price queries
            $table->index('price_with_delay');
        });
    }

    public function down(): void
    {
        Schema::table('supermarket_documents', function (Blueprint $table) {
            $table->dropIndex(['price_with_delay']);
            $table->dropColumn([
                'price_with_delay',
                'price_markup_percent',
            ]);
        });
    }
};
