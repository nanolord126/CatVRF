<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция: расширенные CRM-профили для всех вертикалей.
 *
 * Создаёт таблицы:
 * - crm_auto_profiles (авто, запчасти, СТО)
 * - crm_food_profiles (еда, рестораны, кондитерка)
 * - crm_furniture_profiles (мебель, ремонт, интерьер)
 * - crm_fashion_profiles (одежда, обувь, аксессуары)
 * - crm_fitness_profiles (фитнес, спорт, wellness)
 * - crm_realestate_profiles (недвижимость)
 * - crm_medical_profiles (медицина, фармация, ветеринария)
 * - crm_education_profiles (образование, курсы)
 * - crm_travel_profiles (путешествия, туризм)
 * - crm_pet_profiles (питомцы, ветеринария)
 * - crm_taxi_profiles (такси, каршеринг)
 * - crm_electronics_profiles (электроника, техника)
 * - crm_event_profiles (мероприятия, свадьбы)
 *
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── AUTO ───
        Schema::create('crm_auto_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('vin')->nullable()->index();
            $table->string('car_brand')->nullable();
            $table->string('car_model')->nullable();
            $table->integer('car_year')->nullable();
            $table->string('car_color')->nullable();
            $table->integer('mileage_km')->nullable();
            $table->string('engine_type')->nullable(); // бензин, дизель, электро, гибрид
            $table->string('transmission')->nullable(); // МКПП, АКПП, робот, вариатор
            $table->date('insurance_expires_at')->nullable();
            $table->date('next_service_at')->nullable();
            $table->json('service_history')->nullable(); // [{date, type, mileage, cost}]
            $table->json('preferred_parts_brands')->nullable();
            $table->json('car_preferences')->nullable(); // бюджет, стиль вождения
            $table->string('drivers_license_category')->nullable();
            $table->boolean('has_garage')->default(false);
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['crm_client_id', 'vin']);
        });

        // ─── FOOD ───
        Schema::create('crm_food_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('dietary_restrictions')->nullable(); // vegan, halal, kosher, gluten_free
            $table->json('allergies')->nullable(); // орехи, лактоза, глютен
            $table->json('favorite_cuisines')->nullable(); // итальянская, японская, грузинская
            $table->json('favorite_dishes')->nullable(); // [{dish_id, name, order_count}]
            $table->json('disliked_ingredients')->nullable();
            $table->integer('preferred_spiciness')->nullable(); // 1-5
            $table->integer('daily_calorie_target')->nullable();
            $table->json('macros_target')->nullable(); // {protein, fat, carbs}
            $table->string('meal_plan_type')->nullable(); // balanced, keto, paleo, mediterranean
            $table->integer('avg_order_frequency_days')->nullable();
            $table->decimal('avg_order_amount', 10, 2)->nullable();
            $table->json('delivery_time_preferences')->nullable(); // ['12:00-13:00', '19:00-20:00']
            $table->boolean('is_corporate_client')->default(false);
            $table->integer('corporate_headcount')->nullable();
            $table->json('corporate_schedule')->nullable(); // [{day, meals, headcount}]
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── FURNITURE & CONSTRUCTION ───
        Schema::create('crm_furniture_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('interior_style')->nullable(); // modern, classic, loft, scandinavian
            $table->json('preferred_materials')->nullable(); // дерево, металл, стекло
            $table->json('preferred_colors')->nullable();
            $table->json('room_dimensions')->nullable(); // [{room, width, length, height}]
            $table->decimal('renovation_budget', 14, 2)->nullable();
            $table->string('property_type')->nullable(); // квартира, дом, офис
            $table->integer('property_area_sqm')->nullable();
            $table->integer('rooms_count')->nullable();
            $table->json('renovation_stages')->nullable(); // [{stage, status, deadline, budget}]
            $table->json('purchased_items_history')->nullable();
            $table->boolean('needs_delivery')->default(true);
            $table->boolean('needs_assembly')->default(false);
            $table->boolean('needs_design_project')->default(false);
            $table->json('measurements_data')->nullable(); // сохранённые замеры
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── FASHION ───
        Schema::create('crm_fashion_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('body_type')->nullable(); // hourglass, pear, apple, rectangle, inverted_triangle
            $table->string('color_type')->nullable(); // spring, summer, autumn, winter
            $table->string('style_type')->nullable(); // casual, classic, streetwear, boho, minimalist
            $table->json('sizes')->nullable(); // {top: 'M', bottom: '32', shoes: '42', dress: '44'}
            $table->json('preferred_brands')->nullable();
            $table->json('preferred_colors')->nullable();
            $table->json('disliked_styles')->nullable();
            $table->json('wardrobe_capsules')->nullable(); // [{name, items[], season, occasion}]
            $table->json('wishlist')->nullable(); // [{product_id, added_at}]
            $table->integer('ar_tryons_count')->default(0);
            $table->json('ar_tryons_history')->nullable(); // [{product_id, date, liked}]
            $table->decimal('avg_purchase_amount', 10, 2)->nullable();
            $table->string('preferred_price_range')->nullable(); // economy, mid, premium, luxury
            $table->json('seasonal_preferences')->nullable(); // {spring: [...], summer: [...]}
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── FITNESS ───
        Schema::create('crm_fitness_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->decimal('weight_kg', 5, 1)->nullable();
            $table->decimal('target_weight_kg', 5, 1)->nullable();
            $table->decimal('body_fat_pct', 4, 1)->nullable();
            $table->string('fitness_goal')->nullable(); // weight_loss, muscle_gain, endurance, flexibility, rehab
            $table->string('fitness_level')->nullable(); // beginner, intermediate, advanced, pro
            $table->json('health_conditions')->nullable(); // травмы, ограничения
            $table->json('preferred_activities')->nullable(); // yoga, CrossFit, бег, бокс
            $table->json('disliked_activities')->nullable();
            $table->string('membership_type')->nullable(); // monthly, yearly, unlimited
            $table->date('membership_expires_at')->nullable();
            $table->integer('visits_per_week')->nullable();
            $table->json('training_schedule')->nullable(); // [{day, time, type}]
            $table->json('body_measurements')->nullable(); // {chest, waist, hips, biceps}
            $table->json('progress_photos')->nullable(); // [{date, url}]
            $table->json('supplements_used')->nullable();
            $table->integer('preferred_trainer_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── REAL ESTATE ───
        Schema::create('crm_realestate_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('client_role')->nullable(); // buyer, seller, renter, landlord, investor
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->json('preferred_locations')->nullable(); // [{city, district, address}]
            $table->json('property_requirements')->nullable(); // {rooms_min, area_min, floor_min, floor_max}
            $table->string('property_type_preference')->nullable(); // apartment, house, commercial, land
            $table->boolean('mortgage_needed')->default(false);
            $table->boolean('mortgage_approved')->default(false);
            $table->string('mortgage_bank')->nullable();
            $table->decimal('mortgage_amount', 14, 2)->nullable();
            $table->json('viewed_properties')->nullable(); // [{property_id, date, rating, notes}]
            $table->json('saved_properties')->nullable(); // [{property_id, saved_at}]
            $table->integer('viewings_count')->default(0);
            $table->date('desired_move_date')->nullable();
            $table->json('deal_history')->nullable(); // [{property_id, type, amount, date, status}]
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── MEDICAL ───
        Schema::create('crm_medical_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_type')->nullable();
            $table->json('chronic_conditions')->nullable();
            $table->json('allergies')->nullable(); // медикаменты
            $table->json('current_medications')->nullable(); // [{name, dosage, frequency}]
            $table->json('vaccination_history')->nullable(); // [{vaccine, date, next_date}]
            $table->json('lab_results_history')->nullable(); // [{type, date, values}]
            $table->integer('preferred_doctor_id')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy')->nullable();
            $table->date('insurance_expires_at')->nullable();
            $table->json('appointment_history')->nullable(); // [{date, doctor, specialty, diagnosis}]
            $table->json('prescription_history')->nullable();
            $table->boolean('has_disability')->default(false);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── EDUCATION ───
        Schema::create('crm_education_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('education_level')->nullable(); // school, bachelor, master, phd
            $table->json('learning_goals')->nullable(); // [{goal, priority, deadline}]
            $table->json('preferred_subjects')->nullable();
            $table->json('completed_courses')->nullable(); // [{course_id, name, date, grade, certificate_url}]
            $table->json('active_enrollments')->nullable(); // [{course_id, name, progress_pct, start_date}]
            $table->string('learning_style')->nullable(); // visual, auditory, kinesthetic, reading
            $table->string('preferred_language')->nullable();
            $table->json('schedule_preferences')->nullable(); // [{day, time_from, time_to}]
            $table->string('preferred_format')->nullable(); // online, offline, hybrid
            $table->integer('avg_study_hours_week')->nullable();
            $table->json('certifications')->nullable(); // [{name, issuer, date, url}]
            $table->json('skills_acquired')->nullable();
            $table->decimal('total_spent_on_education', 14, 2)->default(0);
            $table->integer('courses_completed_count')->default(0);
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── TRAVEL ───
        Schema::create('crm_travel_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('passport_country')->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->json('visas')->nullable(); // [{country, type, expires_at}]
            $table->json('travel_preferences')->nullable(); // {style: 'budget|comfort|luxury', type: 'beach|city|nature'}
            $table->json('preferred_destinations')->nullable();
            $table->json('visited_countries')->nullable();
            $table->json('trip_history')->nullable(); // [{destination, dates, type, rating, spent}]
            $table->string('preferred_airline')->nullable();
            $table->string('preferred_hotel_chain')->nullable();
            $table->string('seat_preference')->nullable(); // window, aisle, middle
            $table->string('meal_preference')->nullable(); // standard, vegetarian, halal, kosher
            $table->json('loyalty_programs')->nullable(); // [{program, number, tier}]
            $table->json('travel_companions')->nullable(); // [{name, relation, passport}]
            $table->boolean('needs_transfer')->default(false);
            $table->boolean('needs_insurance')->default(true);
            $table->decimal('avg_trip_budget', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── PET ───
        Schema::create('crm_pet_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('pets')->nullable(); // [{name, species, breed, age, weight, gender, sterilized}]
            $table->json('vaccination_schedule')->nullable(); // [{pet_name, vaccine, date, next_date}]
            $table->json('medical_conditions')->nullable(); // [{pet_name, condition, treatment}]
            $table->json('dietary_needs')->nullable(); // [{pet_name, food_brand, allergies}]
            $table->json('preferred_brands')->nullable(); // корм, лакомства
            $table->json('grooming_schedule')->nullable(); // [{pet_name, type, frequency_days}]
            $table->integer('preferred_vet_id')->nullable();
            $table->json('vet_visit_history')->nullable(); // [{pet_name, date, reason, vet, cost}]
            $table->boolean('needs_pet_sitting')->default(false);
            $table->boolean('needs_dog_walking')->default(false);
            $table->json('insurance_info')->nullable(); // {provider, policy, expires_at}
            $table->decimal('monthly_pet_budget', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── TAXI ───
        Schema::create('crm_taxi_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('frequent_routes')->nullable(); // [{from, to, frequency, avg_cost}]
            $table->json('home_address')->nullable();
            $table->json('work_address')->nullable();
            $table->json('saved_addresses')->nullable(); // [{label, address, lat, lon}]
            $table->string('preferred_car_class')->nullable(); // economy, comfort, business, premium
            $table->string('preferred_payment')->nullable(); // cash, card, corporate
            $table->boolean('is_corporate')->default(false);
            $table->string('corporate_account_id')->nullable();
            $table->decimal('monthly_ride_budget', 10, 2)->nullable();
            $table->integer('total_rides')->default(0);
            $table->decimal('total_spent_rides', 14, 2)->default(0);
            $table->decimal('avg_rating_given', 3, 2)->default(5.0);
            $table->json('preferred_drivers')->nullable(); // [{driver_id, name, rating}]
            $table->json('ride_time_patterns')->nullable(); // {morning: '08:00-09:00', evening: '18:00-19:00'}
            $table->boolean('needs_child_seat')->default(false);
            $table->boolean('needs_pet_friendly')->default(false);
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── ELECTRONICS ───
        Schema::create('crm_electronics_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('owned_devices')->nullable(); // [{type, brand, model, purchase_date, warranty_until}]
            $table->json('preferred_brands')->nullable();
            $table->json('preferred_categories')->nullable(); // smartphones, laptops, audio, gaming
            $table->string('tech_level')->nullable(); // basic, intermediate, advanced, geek
            $table->json('wishlist')->nullable(); // [{product_id, added_at}]
            $table->json('warranty_tracking')->nullable(); // [{device, warranty_until, extended}]
            $table->json('trade_in_history')->nullable(); // [{device, amount, date}]
            $table->json('repair_history')->nullable(); // [{device, issue, date, cost, status}]
            $table->string('preferred_os')->nullable(); // ios, android, windows, macos, linux
            $table->string('preferred_price_range')->nullable(); // budget, mid, flagship
            $table->boolean('interested_in_trade_in')->default(false);
            $table->boolean('wants_extended_warranty')->default(false);
            $table->boolean('subscribed_to_new_releases')->default(false);
            $table->decimal('avg_purchase_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });

        // ─── EVENTS / WEDDING ───
        Schema::create('crm_event_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_client_id')->constrained('crm_clients')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('upcoming_events')->nullable(); // [{type, date, guests_count, budget, status}]
            $table->json('past_events')->nullable(); // [{type, date, venue, rating, spent}]
            $table->json('preferred_venues')->nullable();
            $table->json('preferred_caterers')->nullable();
            $table->json('preferred_decorators')->nullable();
            $table->json('preferred_photographers')->nullable();
            $table->string('event_style')->nullable(); // classic, modern, rustic, boho, glamorous
            $table->decimal('typical_budget', 14, 2)->nullable();
            $table->integer('typical_guest_count')->nullable();
            $table->json('vendor_contacts')->nullable(); // [{vendor_type, name, phone, rating}]
            $table->json('important_dates')->nullable(); // [{label, date}] — юбилеи, годовщины
            $table->boolean('is_event_planner')->default(false); // сам организатор или клиент
            $table->json('checklist_template')->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique('crm_client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_event_profiles');
        Schema::dropIfExists('crm_electronics_profiles');
        Schema::dropIfExists('crm_taxi_profiles');
        Schema::dropIfExists('crm_pet_profiles');
        Schema::dropIfExists('crm_travel_profiles');
        Schema::dropIfExists('crm_education_profiles');
        Schema::dropIfExists('crm_medical_profiles');
        Schema::dropIfExists('crm_realestate_profiles');
        Schema::dropIfExists('crm_fitness_profiles');
        Schema::dropIfExists('crm_fashion_profiles');
        Schema::dropIfExists('crm_furniture_profiles');
        Schema::dropIfExists('crm_food_profiles');
        Schema::dropIfExists('crm_auto_profiles');
    }
};
