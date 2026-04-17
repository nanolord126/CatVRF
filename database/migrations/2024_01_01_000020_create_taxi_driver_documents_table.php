<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_driver_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('type');
            $table->string('document_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('file_mime_type');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('correlation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'driver_id']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_driver_documents');
    }
};
