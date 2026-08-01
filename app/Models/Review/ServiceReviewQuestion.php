<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceReviewQuestion extends Model
{
    use SoftDeletes;

    public const TYPE_SCALE = 'scale';

    public const TYPE_YES_NO = 'yes_no';

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    public const TYPE_TEXT = 'text';

    protected $fillable = [
        'question',
        'type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_SCALE => '1-5 puan',
            self::TYPE_YES_NO => 'Evet / Hayır',
            self::TYPE_SINGLE_CHOICE => 'Tek seçim',
            self::TYPE_TEXT => 'Serbest metin',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function requiresOptions(): bool
    {
        return $this->type === self::TYPE_SINGLE_CHOICE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function reviewItems(): HasMany
    {
        return $this->hasMany(ServiceReviewItem::class, 'question_id');
    }
}
