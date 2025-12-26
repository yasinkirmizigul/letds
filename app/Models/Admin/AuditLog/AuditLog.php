<?php

namespace App\Models\Admin\AuditLog;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id','user_email','user_name',
        'action','route','method','status',
        'ip','user_agent',
        'uri','query','payload','context',
        'duration_ms',
    ];

    protected $casts = [
        'query' => 'array',
        'payload' => 'array',
        'context' => 'array',
    ];
}
