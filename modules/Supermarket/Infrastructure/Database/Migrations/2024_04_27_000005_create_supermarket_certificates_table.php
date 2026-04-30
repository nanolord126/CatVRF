<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('certificate_number')->unique();
            $table->enum('type', ['declaration', 'certificate', 'veterinary', 'other'])->default('certificate');
            $table->string('issued_by');
            $table->date('valid_from');
            $table->date('valid_until');
            $table->string('file_path')->nullable();
            $table->string('document_url')->nullable();
            $table->enum('status', ['valid', 'expired', 'revoked'])->default('valid');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['product_id', 'type']);
            $table->index(['valid_from', 'valid_until']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_certificates');
    }
};
