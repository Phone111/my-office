<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ทะเบียนเกียรติบัตร (ระดับเขต/หน่วยงาน) — ออกเลขต่อหน่วยงาน/ปี พ.ศ.
 */
class AreaCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id', 'cert_no', 'cert_year', 'category', 'title', 'recipient_name',
        'recipient_org', 'issued_date', 'signer_id', 'note', 'issued_by',
    ];

    protected function casts(): array
    {
        return ['issued_date' => 'date'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(CertificateSigner::class, 'signer_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
