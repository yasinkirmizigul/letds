<?php

namespace App\Models\Admin\AuditLog;

use App\Models\Admin\User\User;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeVisibleTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer?->isSuperAdmin()) {
            return $query;
        }

        return $query
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('user_id')
                    ->orWhereNotIn('user_id', User::query()->superAdmins()->select('users.id'));
            })
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('user_email')
                    ->orWhereNotIn('user_email', User::query()->superAdmins()->select('users.email'));
            });
    }

    public function isVisibleTo(?User $viewer): bool
    {
        if ($viewer?->isSuperAdmin()) {
            return true;
        }

        if (! $this->user_id && ! $this->user_email) {
            return true;
        }

        $superAdmin = User::query()->superAdmins();

        if ($this->user_id && $this->user_email) {
            $superAdmin->where(fn (Builder $query) => $query
                ->whereKey($this->user_id)
                ->orWhere('email', $this->user_email));
        } elseif ($this->user_id) {
            $superAdmin->whereKey($this->user_id);
        } else {
            $superAdmin->where('email', $this->user_email);
        }

        return ! $superAdmin->exists();
    }
}
