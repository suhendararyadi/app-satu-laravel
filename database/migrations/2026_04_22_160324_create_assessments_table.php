<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->decimal('max_score', 8, 2)->default(100);
            $table->date('date');
            $table->foreignId('teacher_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
