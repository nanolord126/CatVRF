<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronics_products', function (Blueprint $table) {
            $table->enum('type', [
                'smartphones',
                'laptops',
                'tablets',
                'headphones',
                'tv',
                'cameras',
                'smartwatches',
                'gaming',
                'audio',
                'networking',
                'accessories',
                'wearable',
                'home_automation',
                'car_electronics',
                'appliances',
            ])->nullable()->index()->after('category');
            
            // Add composite index for type + category + brand searches
            $table->index(['type', 'category', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::table('electronics_products', function (Blueprint $table) {
            $table->dropIndex(['type', 'category', 'brand']);
            $table->dropColumn('type');
        });
    }
};
