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
        Schema::create('agency_referrals', function (Blueprint $col) {
            $col->id();
            $col->string('correlation_id')->nullable()->index();
            $col->string('name');
            $col->string('email');
            $col->string('company_name');
            $col->string('suggested_role')->nullable();
            $col->string('status')->default('pending');
            $col->timestamps();
            $col->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_referrals');
    }
};
