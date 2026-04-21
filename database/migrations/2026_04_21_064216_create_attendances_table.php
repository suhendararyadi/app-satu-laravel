<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Satu absensi per kelas per hari per mapel (null = absensi harian)
            $table->unique(['classroom_id', 'date', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
