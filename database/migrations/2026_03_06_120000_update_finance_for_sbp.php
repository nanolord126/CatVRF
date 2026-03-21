<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->text('universal_qr_data')->nullable(); // QR Text payload
            $table->json('sbp_fields')->nullable(); // Additional SBP info
        });
    }
    public function down(): void {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn(['universal_qr_data', 'sbp_fields']);
        });
    }
};
