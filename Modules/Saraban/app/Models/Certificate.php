<?php

namespace Modules\Saraban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * รายการในทะเบียนเลขเกียรติบัตร
 */
class Certificate extends Model
{
    protected $fillable = [
        'certificate_number',
        'title',
        'recipient_name',
        'issued_date',
        'note',
        'issuer_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
        ];
    }

    /**
     * ผู้ออกเกียรติบัตร
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }
}
