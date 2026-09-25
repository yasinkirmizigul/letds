<div class="fixed inset-0 z-[140] hidden bg-foreground/60 p-2 sm:px-4 sm:py-6" data-membership-terms-modal>
    <div class="mx-auto flex h-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-border bg-background shadow-2xl sm:rounded-3xl">
        <div class="flex items-start justify-between gap-3 border-b border-border px-4 py-4 sm:gap-4 sm:px-6 sm:py-5">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Üyelik Bilgilendirmesi</div>
                <h3 class="mt-2 font-display text-xl font-semibold text-foreground sm:text-2xl">{{ $membershipTermsTitle }}</h3>
                <div class="mt-2 text-sm text-muted-foreground">{{ $membershipTermsSummary }}</div>
            </div>
            <button type="button" class="kt-btn kt-btn-light" data-membership-terms-close>Kapat</button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 text-sm leading-7 text-muted-foreground sm:px-6 sm:py-6 sm:leading-8" data-membership-terms-scrollable>
            {!! nl2br(e($membershipTermsContent)) !!}
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-4 sm:px-6 sm:py-5">
            <div class="text-sm text-muted-foreground" data-membership-terms-modal-status>Metnin sonuna ulaştığınızda kabul kutusu aktifleşir.</div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('member.terms.show', ['site_locale' => $siteCurrentLocale]) }}" target="_blank" class="kt-btn kt-btn-light">Tam Sayfada Aç</a>
                <button type="button" class="kt-btn kt-btn-primary" data-membership-terms-close>Okudum, Forma Dön</button>
            </div>
        </div>
    </div>
</div>
