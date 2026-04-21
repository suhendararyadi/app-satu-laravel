<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AcademicYear;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = (int) fake()->year();

        return [
            'team_id' => Team::factory(),
            'name' => $startYear.'/'.(string) ($startYear + 1),
            'start_year' => $startYear,
            'end_year' => $startYear + 1,
            'is_active' => false,
        ];
    }
}
