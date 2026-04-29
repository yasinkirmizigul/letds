@extends('site.layouts.main.app')

@php
    $membershipTermsTitle = $siteSettings->localized('member_terms_title') ?: config('membership_terms.title');
    $membershipTermsSummary = $siteSettings->localized('member_terms_summary') ?: config('membership_terms.summary');
    $membershipTermsContent = $siteSettings->localized('member_terms_content') ?: config('membership_terms.content');
@endphp

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-10">
        <section class="overflow-hidden rounded-[36px] border border-border bg-white/95 shadow-sm">
            <div class="border-b border-border bg-gradient-to-r from-primary/10 via-white to-white px-8 py-8">
                <div class="inline-flex rounded-full border border-primary/20 bg-primary/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-primary">
                    Üyelik Bilgilendirmesi
                </div>
                <h1 class="mt-5 text-4xl font-semibold leading-tight text-foreground">{{ $membershipTermsTitle }}</h1>
                <p class="mt-4 max-w-3xl text-sm leading-8 text-muted-foreground">{{ $membershipTermsSummary }}</p>
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
