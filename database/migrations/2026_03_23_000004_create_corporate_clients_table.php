<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('corporate_clients')) {
            Schema::create('corporate_clients', function (Blueprint $t) {
                $t->id(); $t->uuid('uuid')->unique(); $t->string('tenant_id')->index();
                $t->string('name'); $t->jsonb('tags')->nullable();
                $t->string('correlation_id')->nullable(); $t->timestamps();
                $t->comment('Корпоративные клиенты');
            });
        }
    }
    public function down(): void { Schema::dropIfExists('corporate_clients'); }
};
