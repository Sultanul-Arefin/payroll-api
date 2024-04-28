<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpArticleCategory extends Model
{
    use HasFactory;

    /**
     * @return HasMany
     */
    public function support_ticket(): HasMany
    {
        return $this->hasMany(Support::class, 'category_id', 'id');
    }
}
