<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        // Принудительно используем default-соединение (sqlite) для обхода проблемных настроек вендора
        Schema::create("push_subscriptions", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("subscribable_id");
            $table->string("subscribable_type");
            $table->string("endpoint", 500)->unique();
            $table->string("public_key")->nullable();
            $table->string("auth_token")->nullable();
            $table->string("content_encoding")->nullable();
            $table->timestamps();
            
            $table->index(["subscribable_id", "subscribable_type"], "push_sub_idx");

            $table->string('correlation_id')->nullable()->index();        });
    }

    public function down()
    {
        Schema::dropIfExists("push_subscriptions");
    }
};

