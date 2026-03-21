<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable("messages")) {
            Schema::create("messages", function (Blueprint $table) {
                $table->id();
                $table->uuid("tenant_id")->nullable();
                $table->unsignedBigInteger("sender_id")->nullable();
                $table->unsignedBigInteger("receiver_id")->nullable();
                $table->text("content");
                $table->string("status")->default("sent");
                $table->string("correlation_id")->nullable();
                $table->json("tags")->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists("messages");
    }
};

