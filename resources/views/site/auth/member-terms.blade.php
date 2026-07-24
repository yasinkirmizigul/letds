@extends('site.layouts.main.app')

@php
    $membershipTermsTitle = $siteSettings->localized('member_terms_title') ?: config('membership_terms.title');
    $membershipTermsSummary = $siteSettings->localized('member_terms_summary') ?: config('membership_terms.summary');
    $membershipTermsContent = $siteSettings->localized('member_terms_content') ?: config('membership_terms.content');
@endphp

@section('content')
    <div class="mx-auto max-w-xl px-4 py-10">
        <section class="overflow-hidden rounded-3xl border border-border bg-background shadow-sm">
            <div class="border-b border-border px-8 py-8">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">
                    Üyelik Bilgilendirmesi
                </div>
                <h1 class="mt-5 font-display text-3xl font-semibold leading-tight text-foreground md:text-4xl">{{ $membershipTermsTitle }}</h1>
                <p class="mt-4 text-sm leading-8 text-muted-foreground">{{ $membershipTermsSummary }}</p>
            </div>

            <div class="px-8 py-8">
                <div class="prose prose-slate max-w-none text-sm leading-8 text-muted-foreground">
                    {!! nl2br(e($membershipTermsContent)) !!}
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('member.register', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-primary">Kayıt Ekranına Dön</a>
                    <a href="{{ route('member.login', ['site_locale' => $siteCurrentLocale]) }}" class="kt-btn kt-btn-light">Üye Girişine Git</a>
                </div>
            </div>
        </section>
    </div>
@endsection
