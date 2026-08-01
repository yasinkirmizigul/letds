<?php

namespace App\Models\Review;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReviewItem extends Model
{
    protected $fillable = [
        'service_review_id',
        'question_id',
        'question_text',
        'question_type',
        'question_options',
        'is_required',
        'sort_order',
        'answer',
    ];

    protected function casts(): array
    {
        return [
            'question_options' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'answer' => 'array',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ServiceReview::class, 'service_review_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ServiceReviewQuestion::class, 'question_id')->withTrashed();
    }

    public function answerValue(): mixed
    {
        return data_get($this->answer, 'value');
    }

    public function questionTypeLabel(): string
    {
        return ServiceReviewQuestion::typeOptions()[$this->question_type] ?? $this->question_type;
    }
}
