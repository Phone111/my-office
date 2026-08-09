<?php

namespace Modules\Executive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Executive\Database\Factories\StaffTrainingFactory;

class StaffTraining extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_name',
        'course_name',
        'subject_group',
        'organizer',
        'start_date',
        'end_date',
        'hours',
        'budget_source',
        'location',
        'note',
        'file_path',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'hours' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
