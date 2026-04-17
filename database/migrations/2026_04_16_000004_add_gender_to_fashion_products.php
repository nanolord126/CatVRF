<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fashion_products', function (Blueprint $table) {
            $table->enum('gender', ['men', 'women', 'children', 'unisex'])->default('unisex')->after('color');
            $table->boolean('is_rentable')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('fashion_products', function (Blueprint $table) {
            $table->dropColumn(['gender', 'is_rentable']);
        });
    }
};
