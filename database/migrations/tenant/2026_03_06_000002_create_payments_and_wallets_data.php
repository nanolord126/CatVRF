<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->index();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->float('commission_percent');
            $table->morphs('owner'); // Hotel or Salon
            $table->timestamps();
        });

        Schema::create('bonus_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); 
            $table->decimal('value', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->morphs('owner'); // Hotel or Salon
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
        Schema::dropIfExists('bonus_programs');
    }
};
