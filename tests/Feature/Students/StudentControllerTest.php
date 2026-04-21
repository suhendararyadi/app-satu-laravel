<?php

use App\Models\Academic\StudentEnrollment;
use App\Models\User;

it('user can load their enrollments', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    StudentEnrollment::factory()->create(['user_id' => $user->id]);
    StudentEnrollment::factory()->create(['user_id' => $other->id]);

    expect($user->enrollments)->toHaveCount(1);
});
