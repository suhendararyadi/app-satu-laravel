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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('npsn', 8)->nullable()->unique()->after('slug');
            $table->string('school_type')->nullable()->after('npsn');
            $table->text('address')->nullable()->after('school_type');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
            $table->string('phone', 20)->nullable()->after('postal_code');
            $table->string('email', 255)->nullable()->after('phone');
            $table->string('logo_path')->nullable()->after('email');
            $table->string('accreditation', 5)->nullable()->after('logo_path');
            $table->string('principal_name', 100)->nullable()->after('accreditation');
            $table->unsignedSmallInteger('founded_year')->nullable()->after('principal_name');
            $table->text('vision')->nullable()->after('founded_year');
            $table->text('mission')->nullable()->after('vision');
            $table->text('description')->nullable()->after('mission');
            $table->string('website_theme')->nullable()->default('default')->after('description');
            $table->string('custom_domain')->nullable()->unique()->after('website_theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'npsn',
                'school_type',
                'address',
                'city',
                'province',
                'postal_code',
                'phone',
                'email',
                'logo_path',
                'accreditation',
                'principal_name',
                'founded_year',
                'vision',
                'mission',
                'description',
                'website_theme',
                'custom_domain',
            ]);
        });
    }
};
