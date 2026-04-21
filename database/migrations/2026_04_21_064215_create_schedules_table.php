<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->foreignId('time_slot_id')->constrained()->cascadeOnDelete();
            $table->string('room', 100)->nullable();
            $table->timestamps();

            $table->unique(['semester_id', 'classroom_id', 'day_of_week', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
