<?php

namespace App\Http\Controllers\Admin\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Admin\Ecommerce\EcommerceCoupon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('q'));
        $status = (string) $request->string('status', 'all');

        $coupons = EcommerceCoupon::query()
            ->search($search)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'passive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.ecommerce.coupons.index', [
            'pageTitle' => 'Kupon ve Kampanya Yönetimi',
            'coupons' => $coupons,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'all' => EcommerceCoupon::query()->count(),
                'active' => EcommerceCoupon::query()->where('is_active', true)->count(),
                'expired' => EcommerceCoupon::query()->whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
                'used' => EcommerceCoupon::query()->sum('usage_count'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.ecommerce.coupons.form', [
            'pageTitle' => 'Kupon Oluştur',
            'coupon' => new EcommerceCoupon(['type' => EcommerceCoupon::TYPE_FIXED, 'is_active' => true]),
            'typeOptions' => EcommerceCoupon::typeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EcommerceCoupon::query()->create($this->validated($request));

        return redirect()
            ->route('admin.ecommerce.coupons.index')
            ->with('success', 'Kupon oluşturuldu.');
    }

    public function edit(EcommerceCoupon $coupon): View
    {
        return view('admin.pages.ecommerce.coupons.form', [
            'pageTitle' => 'Kupon Düzenle',
            'coupon' => $coupon,
            'typeOptions' => EcommerceCoupon::typeOptions(),
        ]);
    }

    public function update(Request $request, EcommerceCoupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()
            ->route('admin.ecommerce.coupons.index')
            ->with('success', 'Kupon güncellendi.');
    }

    public function toggle(EcommerceCoupon $coupon): RedirectResponse
    {
        $coupon->forceFill(['is_active' => !$coupon->is_active])->save();

        return back()->with('success', 'Kupon durumu güncellendi.');
    }

    public function destroy(EcommerceCoupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return back()->with('success', 'Kupon silindi.');
    }

    private function validated(Request $request, ?EcommerceCoupon $coupon = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('ecommerce_coupons', 'code')->ignore($coupon?->id)],
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(array_keys(EcommerceCoupon::typeOptions()))],
            'value' => ['nullable', 'numeric', 'min:0'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'max_discount_total' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'applies_to_text' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = Str::upper(trim((string) $validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['value'] = $validated['value'] ?? 0;
        $validated['applies_to'] = collect(preg_split('/[,;\r\n]+/u', (string) ($validated['applies_to_text'] ?? '')))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();
        unset($validated['applies_to_text']);

        return $validated;
    }
}
