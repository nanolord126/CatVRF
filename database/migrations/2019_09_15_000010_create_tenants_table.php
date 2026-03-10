<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            // Custom fields
            $table->string('name');
            $table->string('type'); // 'hotel' or 'beauty'
            $table->string('plan')->default('basic');
            $table->dateTime('trial_ends_at')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->json('data')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tenants');
    }
};
