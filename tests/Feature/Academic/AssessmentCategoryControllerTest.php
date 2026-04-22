<?php

use App\Enums\TeamRole;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeAssessmentCategoryContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('admin can list assessment categories with total weight', function () {
    [$owner, $team] = makeAssessmentCategoryContext();

    AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 40]);
    AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 60]);

    $this->actingAs($owner)
        ->get(route('academic.assessment-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/assessment-categories/index')
            ->has('categories', 2)
            ->where('total_weight', '100.00')
        );
});

it('admin can create assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();

    $this->actingAs($owner)
        ->post(route('academic.assessment-categories.store'), [
            'name' => 'UTS',
            'weight' => 30,
        ])
        ->assertRedirect();

    expect(AssessmentCategory::where('team_id', $team->id)->where('name', 'UTS')->exists())->toBeTrue();
});

it('validates assessment category store rules', function () {
    [$owner] = makeAssessmentCategoryContext();

    $this->actingAs($owner)
        ->post(route('academic.assessment-categories.store'), [])
        ->assertSessionHasErrors(['name', 'weight']);
});

it('admin can update assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);

    $this->actingAs($owner)
        ->patch(route('academic.assessment-categories.update', $category), [
            'name' => 'UAS',
            'weight' => 40,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('UAS');
});

it('admin can delete unused assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);

    $this->actingAs($owner)
        ->delete(route('academic.assessment-categories.destroy', $category))
        ->assertRedirect();

    expect(AssessmentCategory::find($category->id))->toBeNull();
});

it('cannot delete assessment category that has assessments', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);
    Assessment::factory()->create(['assessment_category_id' => $category->id, 'team_id' => $team->id]);
    $owner->switchTeam($team); // UserFactory::afterCreating resets URL defaults when teacher_user_id is created

    $this->actingAs($owner)
        ->delete(route('academic.assessment-categories.destroy', $category))
        ->assertStatus(422);

    expect(AssessmentCategory::find($category->id))->not->toBeNull();
});

it('returns 403 when accessing category of another team', function () {
    [$owner] = makeAssessmentCategoryContext();
    $other = AssessmentCategory::factory()->create();

    $this->actingAs($owner)
        ->patch(route('academic.assessment-categories.update', $other), ['name' => 'X', 'weight' => 10])
        ->assertForbidden();
});

it('teacher cannot access assessment categories', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $teacher = User::factory()->create();
    $team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);
    $owner->switchTeam($team); // restore URL defaults

    $this->actingAs($teacher)
        ->get(route('academic.assessment-categories.index'))
        ->assertForbidden();
});
