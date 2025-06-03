<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use SoftDeletes;
    use HasFactory;

    const ROOT = 1;

        /**
     * Батьківська категорія
     */
    public function parentCategory()
    {
        return $this->belongsTo(BlogCategory::class, 'parent_id', 'id');
    }

    /**
     * Accessor для отримання назви батьківської категорії
     */
    public function getParentTitleAttribute(): string
    {
        $title = $this->parentCategory->title
            ?? ($this->isRoot()
                ? 'Корінь'
                : '???');
        
        return $title;
    }

    /**
     * Перевірка чи об'єкт є кореневим
     */
    public function isRoot(): bool
    {
        return $this->id === BlogCategory::ROOT;
    }
    
    protected $fillable = [
        'title',
        'slug',
        'parent_id',
        'description',
    ];
}