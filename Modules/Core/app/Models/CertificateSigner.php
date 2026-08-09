<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ผู้ลงนามในเกียรติบัตร (ทะเบียนผู้ลงนามต่อหน่วยงาน)
 */
class CertificateSigner extends Model
{
    use HasFactory;

    protected $fillable = ['unit_id', 'name', 'position', 'signature_path', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
