<?php

namespace App\Models\Admin\Project;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflowEvent extends Model
{
    protected $fillable = [
        'analysis_request_id', 'event_type', 'actor_type', 'actor_id', 'body',
        'data', 'member_seen_at', 'provider_seen_at',
        'whatsapp_stage_accepted_at', 'whatsapp_stage_message_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'member_seen_at' => 'datetime',
            'provider_seen_at' => 'datetime',
            'whatsapp_stage_accepted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function analysisRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectAnalysisRequest::class, 'analysis_request_id');
    }

    public function label(): string
    {
        return match ($this->event_type) {
            'project_created' => 'Çalışma alanı açıldı',
            'documents_added' => 'Üye belge paylaştı',
            'report_delivered' => 'Uzman raporu teslim etti',
            'request_created' => 'Üye ek analiz istedi',
            'member_message' => 'Üye yeni mesaj gönderdi',
            'provider_message' => 'Uzman yanıt verdi',
            'project_status_changed' => 'Analiz aşaması değişti',
            'request_status_changed' => 'Ek analiz durumu değişti',
            default => 'Süreç güncellendi',
        };
    }
}
