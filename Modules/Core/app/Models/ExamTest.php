<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExamTest extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'grade', 'created_by'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(ExamQuestion::class, 'exam_test_question')->withPivot('sort')->orderBy('sort');
    }
}
