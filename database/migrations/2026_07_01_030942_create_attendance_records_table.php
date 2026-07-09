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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('attendance_type')->default('office')->index();
            $table->foreignId('duty_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->unsignedInteger('check_in_distance_meters')->nullable();
            $table->string('check_in_location_status')->nullable();
            $table->decimal('check_in_face_distance', 8, 6)->nullable();
            $table->timestamp('check_in_face_verified_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->unsignedInteger('check_out_distance_meters')->nullable();
            $table->string('check_out_location_status')->nullable();
            $table->decimal('check_out_face_distance', 8, 6)->nullable();
            $table->timestamp('check_out_face_verified_at')->nullable();
            $table->string('status')->default('present')->index();
            $table->string('verification_status')->default('approved')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
