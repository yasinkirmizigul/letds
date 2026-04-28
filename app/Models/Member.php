<?php

namespace App\Models;

use App\Models\ContactMessage;
use App\Notifications\MemberResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Member extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'surname',
        'filepath',
        'file_disk',
        'file_original_name',
        'file_mime_type',
        'file_size',
        'email',
        'phone',
        'password',
        'is_active',
        'email_verified_at',
        'membership_terms_accepted_at',
        'membership_terms_version',
        'last_login_at',
        'suspended_at',
        'membership_ended_at',
        'suspension_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'membership_terms_accepted_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'membership_ended_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('surname', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeWithDocument(Builder $query): Builder
    {
        return $query
            ->whereNotNull('filepath')
            ->where('filepath', '!=', '');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->name ?? '') . ' ' . ($this->surname ?? ''));
    }

    public function documentDisk(): string
    {
        $disk = trim((string) $this->file_disk);

        return $disk !== '' ? $disk : 'local';
    }

    public function hasDocument(): bool
    {
        return filled($this->filepath);
    }

    public function documentExists(): bool
    {
        return $this->hasDocument()
            && Storage::disk($this->documentDisk())->exists((string) $this->filepath);
    }

    public function documentName(): ?string
    {
        if (!$this->hasDocument()) {
            return null;
        }

        if (filled($this->file_original_name)) {
            return (string) $this->file_original_name;
        }

        return basename((string) $this->filepath);
    }

    public function documentExtension(): ?string
    {
        $name = $this->documentName();
        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        return $extension !== '' ? $extension : null;
    }

    public function documentSizeLabel(): ?string
    {
        $size = (int) ($this->file_size ?? 0);

        if ($size <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $value = (float) $size;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $unitIndex === 0 ? 0 : 1, ',', '.') . ' ' . $units[$unitIndex];
    }

    public function documentIsImage(): bool
    {
        return str_starts_with((string) $this->file_mime_type, 'image/');
    }

    public function documentIsPdf(): bool
    {
        return str_contains((string) $this->file_mime_type, 'pdf') || $this->documentExtension() === 'pdf';
    }

    public function documentIsPreviewable(): bool
    {
        return $this->documentIsImage() || $this->documentIsPdf();
    }

    public function isSuspended(): bool
    {
        return !$this->is_active;
    }

    public function hasAcceptedMembershipTerms(): bool
    {
        return $this->membership_terms_accepted_at !== null;
    }

    public function hasEndedMembership(): bool
    {
        return $this->membership_ended_at !== null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new MemberResetPasswordNotification((string) $token));
    }

    public function statusLabel(): string
    {
        if ($this->trashed()) {
            return 'Silinmiş';
        }

        return $this->isSuspended() ? 'Askıda' : 'Aktif';
    }

    public function statusBadgeClass(): string
    {
        if ($this->trashed()) {
            return 'kt-badge kt-badge-sm kt-badge-light';
        }

        return $this->isSuspended()
            ? 'kt-badge kt-badge-sm kt-badge-light-warning'
            : 'kt-badge kt-badge-sm kt-badge-light-success';
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Models\Appointment\Appointment::class);
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }
}
