# Fase 4: Penilaian & Rapor — Part 1: Migrations, Models, Factories

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement task-by-task.

**Goal:** Buat 4 migration tables, 4 models, dan 4 factories untuk sistem penilaian.

**Spec:** `docs/superpowers/specs/2026-04-22-fase4-penilaian-rapor-design.md`

**Next parts:**
- Part 2: `docs/superpowers/plans/2026-04-22-fase4-part2-requests-routes-category.md`
- Part 3: `docs/superpowers/plans/2026-04-22-fase4-part3-assessment-reportcard.md`
- Part 4: `docs/superpowers/plans/2026-04-22-fase4-part4-frontend.md`

---

## Task 1: Migrations

**Files:**
- Create: `database/migrations/*_create_assessment_categories_table.php`
- Create: `database/migrations/*_create_assessments_table.php`
- Create: `database/migrations/*_create_scores_table.php`
- Create: `database/migrations/*_create_report_cards_table.php`

- [ ] **Step 1: Create migration files via artisan**

```bash
php artisan make:migration create_assessment_categories_table --no-interaction
php artisan make:migration create_assessments_table --no-interaction
php artisan make:migration create_scores_table --no-interaction
php artisan make:migration create_report_cards_table --no-interaction
```

- [ ] **Step 2: Fill in assessment_categories migration**

```php
public function up(): void
{
    Schema::create('assessment_categories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('team_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->decimal('weight', 5, 2);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('assessment_categories');
}
```

- [ ] **Step 3: Fill in assessments migration**

```php
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
```

- [ ] **Step 4: Fill in scores migration**

```php
public function up(): void
{
    Schema::create('scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
        $table->decimal('score', 8, 2)->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        $table->unique(['assessment_id', 'student_user_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('scores');
}
```

- [ ] **Step 5: Fill in report_cards migration**

```php
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
```

- [ ] **Step 6: Run migrations**

```bash
php artisan migrate --no-interaction
```

Expected: 4 new tables created successfully.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/
git commit -m "feat: add assessment categories, assessments, scores, and report cards migrations"
```

---

## Task 2: Models

**Files:**
- Create: `app/Models/Academic/AssessmentCategory.php`
- Create: `app/Models/Academic/Assessment.php`
- Create: `app/Models/Academic/Score.php`
- Create: `app/Models/Academic/ReportCard.php`

- [ ] **Step 1: Create models via artisan**

```bash
php artisan make:model Academic/AssessmentCategory --no-interaction
php artisan make:model Academic/Assessment --no-interaction
php artisan make:model Academic/Score --no-interaction
php artisan make:model Academic/ReportCard --no-interaction
```

- [ ] **Step 2: Write AssessmentCategory model**

Full content of `app/Models/Academic/AssessmentCategory.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'weight'])]
class AssessmentCategory extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['weight' => 'decimal:2'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}
```

- [ ] **Step 3: Write Assessment model**

Full content of `app/Models/Academic/Assessment.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'classroom_id', 'subject_id', 'semester_id', 'assessment_category_id', 'title', 'max_score', 'date', 'teacher_user_id'])]
class Assessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'max_score' => 'decimal:2',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssessmentCategory::class, 'assessment_category_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
```

- [ ] **Step 4: Write Score model**

Full content of `app/Models/Academic/Score.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'student_user_id', 'score', 'notes'])]
class Score extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
```

- [ ] **Step 5: Write ReportCard model**

Full content of `app/Models/Academic/ReportCard.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'semester_id', 'classroom_id', 'student_user_id', 'generated_by', 'homeroom_notes', 'generated_at'])]
class ReportCard extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
```

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Models/Academic/AssessmentCategory.php app/Models/Academic/Assessment.php app/Models/Academic/Score.php app/Models/Academic/ReportCard.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Models/Academic/
git commit -m "feat: add AssessmentCategory, Assessment, Score, and ReportCard models"
```

---

## Task 3: Factories

**Files:**
- Create: `database/factories/Academic/AssessmentCategoryFactory.php`
- Create: `database/factories/Academic/AssessmentFactory.php`
- Create: `database/factories/Academic/ScoreFactory.php`
- Create: `database/factories/Academic/ReportCardFactory.php`

- [ ] **Step 1: Create factory files via artisan**

```bash
php artisan make:factory Academic/AssessmentCategoryFactory --model=Academic/AssessmentCategory --no-interaction
php artisan make:factory Academic/AssessmentFactory --model=Academic/Assessment --no-interaction
php artisan make:factory Academic/ScoreFactory --model=Academic/Score --no-interaction
php artisan make:factory Academic/ReportCardFactory --model=Academic/ReportCard --no-interaction
```

- [ ] **Step 2: Write AssessmentCategoryFactory**

Full content of `database/factories/Academic/AssessmentCategoryFactory.php`:

```php
<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AssessmentCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentCategory>
 */
class AssessmentCategoryFactory extends Factory
{
    protected $model = AssessmentCategory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->randomElement(['Tugas', 'Ulangan Harian', 'UTS', 'UAS']),
            'weight' => 25.00,
        ];
    }
}
```

- [ ] **Step 3: Write AssessmentFactory**

Full content of `database/factories/Academic/AssessmentFactory.php`:

```php
<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'semester_id' => Semester::factory(),
            'assessment_category_id' => AssessmentCategory::factory(),
            'title' => fake()->sentence(3),
            'max_score' => 100.00,
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'teacher_user_id' => User::factory(),
        ];
    }
}
```

- [ ] **Step 4: Write ScoreFactory**

Full content of `database/factories/Academic/ScoreFactory.php`:

```php
<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Assessment;
use App\Models\Academic\Score;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    protected $model = Score::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_user_id' => User::factory(),
            'score' => fake()->randomFloat(2, 0, 100),
            'notes' => null,
        ];
    }
}
```

- [ ] **Step 5: Write ReportCardFactory**

Full content of `database/factories/Academic/ReportCardFactory.php`:

```php
<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Classroom;
use App\Models\Academic\ReportCard;
use App\Models\Academic\Semester;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCard>
 */
class ReportCardFactory extends Factory
{
    protected $model = ReportCard::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'semester_id' => Semester::factory(),
            'classroom_id' => Classroom::factory(),
            'student_user_id' => User::factory(),
            'generated_by' => User::factory(),
            'homeroom_notes' => null,
            'generated_at' => now(),
        ];
    }
}
```

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint database/factories/Academic/ --format agent
```

- [ ] **Step 7: Commit**

```bash
git add database/factories/Academic/
git commit -m "feat: add AssessmentCategory, Assessment, Score, and ReportCard factories"
```
