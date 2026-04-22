<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->foreignId('classroom_id')->constrained();
            $table->foreignId('student_user_id')->constrained('users');
            $table->foreignId('generated_by')->constrained('users');
            $table->text('homeroom_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unique(['semester_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
