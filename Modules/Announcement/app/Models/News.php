<?php

namespace Modules\Announcement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Announcement\Database\Factories\NewsFactory;

class News extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'news';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'category',
        'excerpt',
        'content',
        'image_path',
        'author',
        'allow_comments',
        'file_path',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'allow_comments' => 'boolean',
        ];
    }

    /**
     * ผู้สร้างข่าว
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // protected static function newFactory(): NewsFactory
    // {
    //     // return NewsFactory::new();
    // }
}
