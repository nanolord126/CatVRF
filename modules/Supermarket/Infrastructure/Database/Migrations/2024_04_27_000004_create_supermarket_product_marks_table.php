<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_product_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('gtin', 14)->index();
            $table->text('data_matrix');
            $table->enum('status', ['introduced', 'in_circulation', 'withdrawn', 'archived'])->default('introduced');
            $table->timestamp('introduced_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['gtin', 'status']);
            $table->index('batch_number');
            $table->index('expiration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_product_marks');
    }
};
