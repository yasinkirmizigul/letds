<?php

namespace App\Models\Admin\Product;

use App\Models\Admin\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    // Admin panel context: hızlı ilerlemek için guarded açık.
    protected $guarded = [];

    // ---- Workflow status ----
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_APPOINTMENT_PENDING = 'appointment_pending';

    // Project ile aynı sözleşme: STATUS_OPTIONS + helperlar
    public const STATUS_OPTIONS = [
        self::STATUS_APPOINTMENT_PENDING => [
            'label' => 'Randevu Bekliyor',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-warning',
            'order' => 10,
        ],
        self::STATUS_DRAFT => [
            'label' => 'Taslak',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light',
            'order' => 20,
        ],
        self::STATUS_ACTIVE => [
            'label' => 'Aktif',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-success',
            'order' => 30,
        ],
        self::STATUS_ARCHIVED => [
            'label' => 'Arşiv',
            'badge' => 'kt-badge kt-badge-sm kt-badge-light-danger',
            'order' => 40,
        ],
    ];

    public static function statusOptionsSorted(): array
    {
        $opts = self::STATUS_OPTIONS;
        uasort($opts, fn ($a, $b) => (int)($a['order'] ?? 0) <=> (int)($b['order'] ?? 0));
        return $opts;
    }

    public static function statusLabel(?string $key): string
    {
        $k = $key ?: self::STATUS_APPOINTMENT_PENDING;
        return self::STATUS_OPTIONS[$k]['label'] ?? $k;
    }

    public static function statusBadgeClass(?string $key): string
    {
        $k = $key ?: self::STATUS_APPOINTMENT_PENDING;
        return self::STATUS_OPTIONS[$k]['badge'] ?? 'kt-badge kt-badge-sm kt-badge-light';
    }

    // ---- Relations ----
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    // ---- Featured media helper ----
    public function featuredMediaUrl(): ?string
    {
        // Project'te kullanılan pivot: mediables (collection=featured)
        $mediaId = DB::table('mediables')
            ->where('mediable_type', self::class)
            ->where('mediable_id', $this->id)
            ->where('collection', 'featured')
            ->orderBy('order')
            ->value('media_id');

        if (!$mediaId) return null;

        // Media tablonuzun adı "media" ve url alanı/ methodu proje tarafında farklı olabilir.
        // En güvenlisi: Media modelinizde getUrl() / url / path neyse ona bağlamak.
        // Bu helper burada null dönebilir; index blade'de fallback var.
        $row = DB::table('media')->where('id', $mediaId)->first();
        if (!$row) return null;

        // En yaygın senaryo: media.path (storage relative) veya media.url
        if (isset($row->url) && $row->url) return (string) $row->url;
        if (isset($row->path) && $row->path) return asset('storage/' . ltrim((string)$row->path, '/'));
        if (isset($row->file_path) && $row->file_path) return asset('storage/' . ltrim((string)$row->file_path, '/'));

        return null;
    }
}
