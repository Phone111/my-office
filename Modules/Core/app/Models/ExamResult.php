<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = ['exam_run_id', 'school_unit_id', 'students', 'passed', 'avg_percent', 'note', 'entered_by'];

    protected function casts(): array
    {
        return ['avg_percent' => 'decimal:2'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'school_unit_id');
    }
}
