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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_consent_at')->nullable()->after('email_verified_at');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active']);
        });

        Schema::create('entrepreneur_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->json('coverage_zones')->nullable();
            $table->timestamps();
        });

        Schema::create('establishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('photo_path')->nullable();
            $table->string('type');
            $table->string('address');
            $table->string('province')->default('Cotopaxi');
            $table->string('canton')->default('Latacunga');
            $table->string('parish')->nullable();
            $table->string('sector')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('reception_status')->default('por_confirmar');
            $table->string('verification_status')->default('pendiente');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->string('commercial_contact_name')->nullable();
            $table->string('commercial_phone', 20)->nullable();
            $table->string('commercial_email')->nullable();
            $table->string('authorized_channel')->nullable();
            $table->json('attention_hours')->nullable();
            $table->json('supplier_attention_hours')->nullable();
            $table->json('entry_requirements')->nullable();
            $table->json('purchase_conditions')->nullable();
            $table->string('supermarket_purchase_mode')->nullable();
            $table->string('central_office_contact')->nullable();
            $table->boolean('has_refrigeration')->default(false);
            $table->string('refrigeration_capacity')->nullable();
            $table->decimal('weekly_purchase_volume', 12, 2)->nullable();
            $table->string('supply_frequency')->nullable();
            $table->timestamp('privacy_consent_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'reception_status', 'verification_status']);
            $table->index(['canton', 'parish', 'sector']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('establishment_categories', function (Blueprint $table) {
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['establishment_id', 'category_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrepreneur_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('presentation');
            $table->decimal('wholesale_price', 12, 2)->nullable();
            $table->decimal('suggested_retail_price', 12, 2)->nullable();
            $table->decimal('minimum_order', 12, 2)->default(1);
            $table->decimal('weekly_capacity', 12, 2)->nullable();
            $table->unsignedInteger('shelf_life_days')->nullable();
            $table->string('storage_conditions')->nullable();
            $table->decimal('required_temperature_min', 5, 2)->nullable();
            $table->decimal('required_temperature_max', 5, 2)->nullable();
            $table->json('coverage_zones')->nullable();
            $table->string('delivery_frequency')->nullable();
            $table->json('documentation')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['category_id', 'is_public']);
            $table->index(['entrepreneur_profile_id', 'category_id']);
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrepreneur_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('por_contactar');
            $table->text('private_notes')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('agreed_conditions')->nullable();
            $table->string('closed_reason')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['entrepreneur_profile_id', 'establishment_id'],
                'opportunities_profile_establishment_unique',
            );
            $table->index(
                ['entrepreneur_profile_id', 'status', 'next_follow_up_at'],
                'opportunities_profile_status_follow_up_index',
            );
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by')->constrained('users')->restrictOnDelete();
            $table->text('message');
            $table->string('status')->default('enviada');
            $table->text('merchant_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['opportunity_id', 'status']);
        });

        Schema::create('proposal_products', function (Blueprint $table) {
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->primary(['proposal_id', 'product_id']);
        });

        Schema::create('establishment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->string('status')->default('abierto');
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'establishment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('establishment_reports');
        Schema::dropIfExists('proposal_products');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('products');
        Schema::dropIfExists('establishment_categories');
        Schema::dropIfExists('establishments');
        Schema::dropIfExists('entrepreneur_profiles');
        Schema::dropIfExists('categories');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('privacy_consent_at');
        });
    }
};
