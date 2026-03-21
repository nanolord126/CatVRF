<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('central')->hasTable('fraud_attempts')) {
            Schema::connection('central')->create('fraud_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('operation_type')->comment('Type: payment_init, card_bind, payout, etc');
                $table->string('ip_address')->nullable();
                $table->string('device_fingerprint')->nullable();
                $table->float('ml_score')->default(0)->comment('Fraud score 0-1');
                $table->string('ml_version')->default('v1-rules')->comment('ML model version');
                $table->string('decision')->default('allow')->comment('allow, block, review');
                $table->string('reason')->nullable()->comment('Reason for block/review');
                $table->json('features_json')->nullable()->comment('Features used for scoring');
                $table->string('correlation_id')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('operation_type');
                $table->index('decision');
                $table->index('ml_score');
                $table->comment('Fraud detection attempts and scores');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('fraud_attempts');
    }
};
