<?php

namespace App\Models\Schedule;

use App\Enums\AttendanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'student_user_id', 'status', 'notes'])]
class AttendanceRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
