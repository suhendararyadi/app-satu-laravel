<?php

use App\Enums\SchoolType;
use App\Models\Team;

test('school fields are saved correctly', function () {
    $team = Team::factory()->create([
        'npsn' => '12345678',
        'school_type' => SchoolType::Sma->value,
        'address' => 'Jl. Merdeka No. 1',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'postal_code' => '10110',
        'phone' => '021-12345678',
        'email' => 'info@sekolah.sch.id',
        'logo_path' => 'logos/logo.png',
        'accreditation' => 'A',
        'principal_name' => 'Budi Santoso',
        'founded_year' => 1990,
        'vision' => 'Menjadi sekolah terbaik',
        'mission' => 'Mendidik generasi bangsa',
        'description' => 'Sekolah negeri unggulan',
        'website_theme' => 'modern',
        'custom_domain' => null,
    ]);

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'npsn' => '12345678',
        'school_type' => 'SMA',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'postal_code' => '10110',
        'accreditation' => 'A',
        'principal_name' => 'Budi Santoso',
        'founded_year' => 1990,
        'website_theme' => 'modern',
    ]);
});

test('school_type is cast to SchoolType enum', function () {
    $team = Team::factory()->create([
        'school_type' => SchoolType::Smk->value,
    ]);

    expect($team->fresh()->school_type)->toBeInstanceOf(SchoolType::class)
        ->and($team->fresh()->school_type)->toBe(SchoolType::Smk);
});

test('factory school state creates team with school fields populated', function () {
    $team = Team::factory()->school()->create();

    expect($team->npsn)->not->toBeNull()
        ->and($team->school_type)->toBeInstanceOf(SchoolType::class)
        ->and($team->address)->not->toBeNull()
        ->and($team->city)->not->toBeNull()
        ->and($team->province)->not->toBeNull()
        ->and($team->postal_code)->not->toBeNull()
        ->and($team->phone)->not->toBeNull()
        ->and($team->email)->not->toBeNull()
        ->and($team->logo_path)->not->toBeNull()
        ->and($team->accreditation)->not->toBeNull()
        ->and($team->principal_name)->not->toBeNull()
        ->and($team->founded_year)->not->toBeNull()
        ->and($team->vision)->not->toBeNull()
        ->and($team->mission)->not->toBeNull()
        ->and($team->description)->not->toBeNull()
        ->and($team->website_theme)->not->toBeNull()
        ->and($team->founded_year)->toBeInt()
        ->and($team->school_type)->toBeInstanceOf(SchoolType::class)
        ->and($team->npsn)->toHaveLength(8);
});
