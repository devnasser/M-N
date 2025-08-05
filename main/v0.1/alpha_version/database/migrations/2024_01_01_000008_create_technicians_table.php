<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('specializations');
            $table->json('certifications')->nullable();
            $table->integer('experience_years');
            $table->decimal('hourly_rate', 8, 2);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('total_appointments')->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(20);
            $table->json('working_hours')->nullable();
            $table->json('service_areas')->nullable();
            $table->json('payment_info')->nullable();
            $table->json('documents')->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_en')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technicians');
    }
}; 