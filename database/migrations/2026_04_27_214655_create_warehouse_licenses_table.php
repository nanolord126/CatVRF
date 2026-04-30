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
        Schema::create('warehouse_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->enum('license_type', [
                'pharmacy',
                'controlled_substances',
                'medical_devices',
                'storage_pharma',
                'storage_narcotics'
            ]);
            $table->string('license_number')->unique();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->string('issuing_authority');
            $table->enum('status', ['active', 'expired', 'suspended', 'revoked'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index(['warehouse_id', 'license_type']);
            $table->index(['status', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_licenses');
    }
};
