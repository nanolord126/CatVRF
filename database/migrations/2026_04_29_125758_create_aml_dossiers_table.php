<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aml_dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('kyc_level')->default('simplified'); // simplified / standard / enhanced
            $table->string('full_name')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_issue_date')->nullable();
            $table->string('passport_issuer')->nullable();
            $table->string('inn')->nullable();
            $table->string('snils')->nullable();
            $table->string('source_of_funds')->nullable();
            $table->json('document_urls')->nullable(); // encrypted storage paths
            $table->string('status')->default('pending'); // pending / verified / rejected
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('retention_until')->nullable(); // 5 years per ФЗ-115
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index(['kyc_level', 'status']);
            $table->index('retention_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aml_dossiers');
    }
};
