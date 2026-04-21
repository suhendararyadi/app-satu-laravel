<?php

namespace App\Models\Academic;

use App\Enums\GuardianRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'guardian_id', 'relationship'])]
class Guardian extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }
}
