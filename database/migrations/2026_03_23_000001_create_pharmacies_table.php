<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pharmacies')) {
            Schema::create('pharmacies', function (Blueprint $t) {
                $t->id(); $t->uuid('uuid')->unique(); $t->string('tenant_id')->index();
                $t->string('name'); $t->string('address'); $t->jsonb('tags')->nullable();
                $t->string('correlation_id')->nullable(); $t->timestamps();
                $t->comment('Аптеки');
            });
        }
    }
    public function down(): void { Schema::dropIfExists('pharmacies'); }
};
