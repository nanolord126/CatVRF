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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'parent_id')) {
                $table->string('parent_id')->nullable()->index();
            }
            if (!Schema::hasColumn('tenants', 'business_group_id')) {
                $table->foreignId('business_group_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('tenants', 'type')) {
                $table->string('type')->default('standalone'); // standalone, head, branch
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'business_group_id', 'type']);
        });
    }
};
