<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamRun extends Model
{
    use HasFactory;

    protected $fillable = ['exam_test_id', 'name', 'academic_year', 'round', 'created_by'];

    public function test(): BelongsTo
    {
        return $this->belongsTo(ExamTest::class, 'exam_test_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
