# Plan 005: İkincil sayfaları sisteme hizala — auth, iletişim, CMS sayfası, hesap; SEO skoru sızıntısını kaldır

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan
> in `plans/README.md` — unless a reviewer dispatched you and told you they
> maintain the index.
>
> **Drift check (run first)**: `git diff --stat 9c6da29..HEAD -- resources/views/site/`
> 001–003'ün layout/home değişiklikleri beklenendir; bu planın in-scope dosyalarında başka değişiklik varsa alıntılarla karşılaştır, uyuşmazlıkta STOP.

## Status

- **Priority**: P2
- **Effort**: L
- **Risk**: LOW
- **Depends on**: plans/001-site-design-foundation.md (002/003 önerilir ama zorunlu değil)
- **Category**: bug (iç metrik sızıntısı) + direction (design tutarlılığı)
- **Planned at**: commit `9c6da29`, 2026-07-23

## Why this matters

Ana sayfa ve layout yenilenince ikincil sayfalar eski şablon dilinde kalır ve tutarsızlık her sayfa geçişinde hissedilir. Ayrıca CMS sayfa şablonu herkese açık kenar çubuğunda **iç yönetim metriği** gösteriyor: `page.blade.php:45`'te "SEO skoru: %78" gibi bir satır ziyaretçiye sunuluyor. Bu plan tüm ikincil sayfaları 001'in tasarım diline hizalar ve sızıntıyı kaldırır.

## Current state

- `resources/views/site/cms/page.blade.php` (107 satır) — hero kartı (kicker + ikon kutusu + başlık + görsel kolonu), içerik `article` kartı + sticky `aside` (satır 40-59): "Sayfa özeti" kutusu şunları listeler:
  ```blade
  <div>{{ $siteSettings->uiLine('page_reading_time_label') }}: {{ $page->readingTimeMinutes() }} dk</div>
  <div>{{ $siteSettings->uiLine('page_seo_score_label') }}: %{{ $page->seoCompletenessScore() }}</div>   {{-- SİLİNECEK --}}
  <div>{{ $siteSettings->uiLine('page_link_label') }}: /{{ $page->slugForLocale($siteCurrentLocale) }}</div>
  ```
  Devamında sayaç kartları ve SSS kartları (ana sayfadakiyle aynı eski desen).
- `resources/views/site/auth/member-login.blade.php` (116 satır) — sol tanıtım paneli (`bg-slate-950`, iki "Avantaj/Güvenlik" cam kartı) + sağ form kartı; form alanları `kt-input`, hata `text-danger`.
- `resources/views/site/contact-messages/create.blade.php` (312 satır) — `kt-card` + `kt-alert` + `app-surface-card` karışımı; alanlar `kt-input`/`kt-select`, `data-kt-select` attribute'ları ve `#contact-message-page`, `#contactRecipient` id'leri JS'e bağlı (`resources/js/site/contact-messages/create.js`).
- Okunmadan kapsama alınan dosyalar (executor önce OKUMALI): `auth/member-register.blade.php`, `auth/member-forgot-password.blade.php`, `auth/member-reset-password.blade.php`, `auth/member-terms.blade.php`, `account/show.blade.php`. `member-register` sayfasının JS'i var: `resources/js/site/auth/member-register.js` — form alan adları/id'leri JS sözleşmesidir.
- 001 sonrası kullanılabilir: `font-display`, `[data-reveal]`, rafine token'lar; 002 sonrası: Alpine.

## Commands you will need

| Purpose | Command | Expected on success |
|---|---|---|
| Install | `npm install` | exit 0 |
| Build | `npm run build` | exit 0 |

## Scope

**In scope**:
- `resources/views/site/cms/page.blade.php`
- `resources/views/site/auth/*.blade.php` (5 dosya)
- `resources/views/site/contact-messages/create.blade.php`
- `resources/views/site/account/show.blade.php`

**Out of scope**:
- Tüm JS dosyaları (form id/name sözleşmeleri değişmeyeceği için gerek yok), controller/model/route, `app.css`, layout (002 bitirdi), `home.blade.php` (003 bitirdi), admin, e-posta şablonları (`resources/views/emails/**`).

## Git workflow

- Branch: `advisor/site-redesign`
- Commit: sayfa grubu başına bir commit (`cms sayfa şablonu hizalandı`, `auth sayfaları hizalandı`, ...)
- Push/PR yok.

## Ortak dönüşüm kuralları (her sayfaya uygula)

1. `h1`/`h2` başlıklara `font-display` ekle; h1 ölçüsü `text-3xl md:text-4xl`.
2. Kicker deseni: `text-xs font-semibold uppercase tracking-[0.18em] text-primary` (eski `tracking-[0.24em]`/`[0.28em]` değerlerini buna indir).
3. Köşe yarıçapı: `rounded-[36px]`/`[32px]`/`[28px]` özel değerlerini `rounded-3xl`e; küçük kutularda `rounded-2xl`e indir.
4. `bg-white/85`, `bg-white/90`, `bg-slate-950`, `bg-slate-100` gibi hard-coded renkleri token'lara çevir: `bg-background`, koyu paneller için `bg-foreground text-background`.
5. İkon kutusu + başlık kalıbını (`inline-flex size-12 ... bg-primary/10 text-primary` + `ki-*`) kaldır; başlık tipografisi taşısın.
6. Form alanları: `kt-input`/`kt-select`/`kt-checkbox` sınıfları ve TÜM `name`/`id`/`data-*` attribute'ları AYNEN kalır; yalnızca çevreleyen düzen/etiket tipografisi değişir. Hata mesajı deseni: `<div class="mt-1.5 text-xs text-danger">`.
7. YASAK: kartlarda 1px'ten kalın renkli sol/sağ kenarlık (`border-l-4` vb.), gradient text, cam efektli dekoratif kartlar.
8. Sayfa üst bölümlerine `data-reveal` ekleme SERBEST ama zorunlu değil (form sayfalarında kullanma — form anında görünmeli).

## Steps

### Step 1: cms/page.blade.php

- Hero kartını kaldır; çıplak editoryal başlık bloğuna çevir: kicker (varsa `hero_kicker`) → `h1 font-display` → excerpt `max-w-2xl text-base leading-8 text-muted-foreground`. Öne çıkan görsel varsa başlığın ALTINDA tam genişlik `rounded-3xl` görsel (`aspect-[21/9] object-cover`).
- İçerik: `article` kartını sadeleştir — `rounded-3xl border border-border bg-background p-6 lg:p-10`; tipografi için mevcut `leading-8` korunur.
- Kenar çubuğu "Sayfa özeti" kutusundan **SEO skoru satırını sil** (`seoCompletenessScore` çağrısı görünümden tamamen çıkar); okuma süresi + link satırları kalabilir. "Hızlı işlemler" kutusu `rounded-2xl` + buton stilleriyle kalır.
- Sayaç ve SSS bölümlerini 003'teki desenlerle değiştir: sayaçlar `border-y` stat bandı (home Step 3 ile aynı markup), SSS `divide-y` listesi + `group-open:rotate-45` artı ikonu (home Step 5 ile aynı).

**Verify**: `grep -c "seoCompletenessScore" resources/views/site/cms/page.blade.php` → 0; `npm run build` → exit 0.

### Step 2: Auth sayfaları (5 dosya)

`member-login.blade.php` deseni (diğer 4 dosyaya da aynı dil uygulanır):

- Sol panel: `bg-slate-950` → `bg-foreground text-background`, `rounded-[36px]` → `rounded-3xl`; içindeki iki cam kart ("Avantaj/Hızlı Erişim", "Güvenlik/Yetkili Kullanım") kaldırılır; yerine `border-t border-background/15 pt-6` ile ayrılmış iki kısa satır: küçük etiket + tek cümle (aynı metinler, kart süsü olmadan).
- Sağ form paneli: `rounded-3xl border border-border bg-background p-6 lg:p-8`; başlık `font-display`; alanlar/route'lar aynen.
- Session/status/hata kutuları: `rounded-2xl border border-success/25 bg-success/10 px-4 py-3 text-sm text-success` deseni (primary/danger için eşdeğeri).
- `member-register`: executor dosyayı ve `resources/js/site/auth/member-register.js`'i OKUR; form alan adlarını/id'lerini değiştirmeden aynı dili uygular. Çok adımlıysa adım göstergesini `bg-primary/10 text-primary` numara rozetleriyle stilize eder.
- `member-terms`, `forgot-password`, `reset-password`: tek kolon dar (`max-w-xl`) düzen, `font-display` başlık, aynı form dili.

**Verify**: `npm run build` → exit 0; `grep -rc "bg-slate-950" resources/views/site/auth/` → her dosyada 0; login formundaki `name="email"`, `name="password"`, `name="remember"` alanları duruyor (grep ile üçünü doğrula).

### Step 3: contact-messages/create.blade.php

- `kt-card` sarmalayıcıyı `rounded-3xl border border-border bg-background` yüzeye çevir; başlık bloğu ortak kurallara göre.
- `app-surface-card` bilgi kutuları (`Üye bilgilerin otomatik kullanılacak` / `Gönderen bilgileri`): `rounded-2xl border border-border bg-muted/40 p-4` sade yüzey; `kt-badge` rozetleri kalabilir.
- Form alanlarının TÜMÜ (`recipient_user_id`, `contact_channels`, name/email/telefon, konu, öncelik, mesaj, `data-kt-select` attribute'ları, `#contact-message-page` data-* attribute'ları) aynen korunur.
- Sağ kenar bilgi kolonu varsa (dosyanın 120. satırdan sonrası — executor tamamını okur) aynı dile hizalanır.

**Verify**: `npm run build` → exit 0; `grep -c "contact-message-page" resources/views/site/contact-messages/create.blade.php` → ≥ 1; `grep -c "data-kt-select" resources/views/site/contact-messages/create.blade.php` → değişiklik öncesiyle aynı sayı (önce say, not al).

### Step 4: account/show.blade.php

Executor dosyayı okur; ortak dönüşüm kurallarını uygular (başlık tipografisi, kart sadeleşmesi, token renkleri). Form/link route'ları aynen kalır.

**Verify**: `npm run build` → exit 0; `grep -c "font-display" resources/views/site/account/show.blade.php` → ≥ 1.

## Test plan

Test altyapısı yok. Kod incelemesi (NOTES'a): her formda `@csrf`, `name` attribute'ları ve `@error` dalları korunmuş; `old(...)` çağrıları kaybolmamış (`grep -c "old(" <dosya>` önce/sonra aynı).

## Done criteria

- [ ] `npm run build` exit 0
- [ ] `grep -rc "seoCompletenessScore" resources/views/site/` → 0
- [ ] `grep -rc "bg-slate-950" resources/views/site/` → 0
- [ ] `grep -rc "rounded-\[3[26]px\]\|rounded-\[28px\]" resources/views/site/` → 0
- [ ] `grep -rn "border-l-2\|border-l-4\|border-r-2\|border-r-4" resources/views/site/` → 0 eşleşme
- [ ] Her form dosyasında `@csrf` sayısı değişiklik öncesiyle aynı
- [ ] `git status` — in-scope dışı değişiklik yok
- [ ] `plans/README.md` güncellendi (reviewer aksini söylemediyse)

## STOP conditions

- Okunmamış dosyalardan biri (`member-register`, `account/show` vb.) beklenmedik şekilde JS'e sıkı bağlı karmaşık bir widget içeriyorsa ve düzen değişikliği sözleşmeyi bozacaksa — o dosyayı atla, NOTES'a yaz, kalanlarla devam et; ikiden fazla dosya atlanıyorsa STOP.
- `uiLine('page_seo_score_label')` başka görünümlerde de kullanılıyorsa yalnızca bu görünümden kaldır; admin'e dokunma.

## Maintenance notes

- Yeni site sayfası eklenirken bu plandaki "Ortak dönüşüm kuralları" başlangıç kontrat listesidir.
- `seoCompletenessScore()` modeli/metodu DURUYOR (admin kullanıyor olabilir) — yalnızca herkese açık görünümden çıkarıldı.
- İleride e-posta şablonları (`resources/views/emails/**`) da aynı dile hizalanmalı (bilinçli erteleme; e-posta CSS kısıtları farklı).
