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
        Schema::create('settlement_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // invoice, act, upd
            $table->string('number')->unique();
            $table->date('document_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('RUB');
            $table->string('status')->default('draft'); // draft, sent, signed, cancelled
            $table->string('file_path')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->json('meta')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_documents');
    }
};
