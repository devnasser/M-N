<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('vehicle_type');
            $table->string('vehicle_model');
            $table->integer('vehicle_year');
            $table->string('license_number')->unique();
            $table->date('license_expiry');
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->json('current_location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('total_deliveries')->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(15);
            $table->json('preferred_areas')->nullable();
            $table->json('working_hours')->nullable();
            $table->json('payment_info')->nullable();
            $table->json('documents')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
}; 