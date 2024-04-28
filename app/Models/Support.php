<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Support extends Model
{
    use HasFactory;

    public const CLOSED = 0;
    public const ACTIVE = 1;
    public const PENDING = 2;

    protected $fillable = [
        'created_by',
        'category_id',
        'subject',
        'page_name',
        'status',
        'updated_by'
    ];

    /**
     * @return BelongsTo
     */
    public function help_article_category(): BelongsTo
    {
        return $this->belongsTo(HelpArticleCategory::class, 'category_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function help_article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'page_name', 'id');
    }
}
