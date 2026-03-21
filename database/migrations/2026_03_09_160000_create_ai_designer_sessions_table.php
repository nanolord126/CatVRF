<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Сессии дизайна интерьера (Interior Designer AI)
        Schema::create("interior_design_sessions", function (Blueprint $table) {
            $table->id();
            $table->string("tenant_id")->index();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("room_photo")->nullable();
            $table->json("ai_analysis")->comment("Стиль, освещение, палитра");
            $table->json("selected_items")->comment("ID товаров из Inventory, их координаты в 3D");
            $table->decimal("total_amount", 15, 2)->default(0);
            $table->string("status")->default("draft"); // draft, generated, ordered
            $table->string("correlation_id")->index();
            $table->timestamps();

            $table->foreign("tenant_id")->references("id")->on("tenants")->onDelete("cascade");
        });

        // Сессии бьюти-примерки (Beauty Try-On AI)
        Schema::create("beauty_try_on_sessions", function (Blueprint $table) {
            $table->id();
            $table->string("tenant_id")->index();
            $table->unsignedBigInteger("user_id")->nullable();
            $table->string("client_photo");
            $table->string("try_on_type"); // hair, makeup, skin_care
            $table->json("parameters")->comment("Цвет, стиль, материалы салона");
            $table->string("result_image_url")->nullable();
            $table->json("used_inventory_items")->comment("ID расходников из Inventory");
            $table->string("correlation_id")->index();
            $table->timestamps();

            $table->foreign("tenant_id")->references("id")->on("tenants")->onDelete("cascade");
        });

        // Расширение таблицы заказов для учета AI-комиссии
        if (Schema::hasTable("orders")) {
            Schema::table("orders", function (Blueprint $table) {
                $table->decimal("ai_commission_amount", 15, 2)->default(0)->after("total");
                $table->boolean("is_ai_generated")->default(false)->after("ai_commission_amount");
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists("interior_design_sessions");
        Schema::dropIfExists("beauty_try_on_sessions");
        if (Schema::hasTable("orders")) {
            Schema::table("orders", function (Blueprint $table) {
                $table->dropColumn(["ai_commission_amount", "is_ai_generated"]);
            });
        }
    }
};
