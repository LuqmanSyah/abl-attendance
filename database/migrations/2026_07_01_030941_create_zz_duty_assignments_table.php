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
        Schema::create('duty_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title');
            $table->string('location_name');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['employee_id', 'starts_at', 'ends_at']);
            $table->index(['supervisor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duty_assignments');
    }
};
