# Plan 002: Site header, navigasyon ve footer'ı yeniden tasarla — mobil menü, erişilebilir dropdown'lar, editoryal footer

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan
> in `plans/README.md` — unless a reviewer dispatched you and told you they
> maintain the index.
>
> **Drift check (run first)**: `git diff --stat 9c6da29..HEAD -- resources/views/site/layouts/main/app.blade.php resources/js/site/app.js`
> Plan 001 bu dosyalara dokunduysa bu beklenen bir durumdur (body'ye `site-shell` sınıfı ve reveal JS eklenmiş olmalı); onun dışındaki uyuşmazlık STOP nedenidir.

## Status

- **Priority**: P1
- **Effort**: M
- **Risk**: MED
- **Depends on**: plans/001-site-design-foundation.md (DONE olmalı)
- **Category**: direction (UX/design) + bug (mobil menü yok)
- **Planned at**: commit `9c6da29`, 2026-07-23

## Why this matters

Sitenin **mobil menüsü yok**: `resources/views/site/layouts/main/app.blade.php:35-119` header'ı `flex-col` + `flex-wrap` ile mobilde tüm nav linklerini ve 4-5 buton/aksiyonu alt alta yığıyor — mobil ziyaretçi ekranın yarısını header'a harcıyor. Dropdown'lar `<details>` ile yapılmış (satır 55-69, 80-91): dışarı tıklayınca kapanmıyor, birden fazlası aynı anda açık kalabiliyor, klavye/ARIA desteği yok. Footer üç eş sütunluk düz bir liste; marka hissi vermiyor. Bu plan header'ı tek satırlı, mobilde hamburger'lı, erişilebilir bir yapıya; footer'ı editoryal bir kapanışa dönüştürür.

## Current state

- `resources/views/site/layouts/main/app.blade.php` — tüm site iskeleti tek dosyada:
  - Satır 20: sayfa sarmalayıcı: `bg-[radial-gradient(circle_at_top_left,rgba(62,151,255,0.12),transparent_24%),linear-gradient(180deg,#f8fafc_0%,#ffffff_100%)]` (hard-coded renkler — 001'in token'larına uymuyor)
  - Satır 34: `<header class="sticky top-0 z-40 border-b border-border/80 bg-white/85 backdrop-blur-xl">`
  - Satır 37-45: logo — baş harflerden `size-12 rounded-2xl bg-primary` kare + site adı + tagline
  - Satır 48-76: nav — aktif link `bg-primary text-white` dolu pill; alt menüler `<details class="group relative">` + `absolute` panel
  - Satır 78-118: sağ aksiyonlar — dil seçici (`<details>`), iletişim, üye giriş/kayıt veya hesap/panel/çıkış (`kt-btn kt-btn-light` / `kt-btn-primary`)
  - Satır 126-177: footer — 3 sütun grid (marka+iletişim / navigasyon / sosyal), `uppercase tracking-[0.24em]` etiketler
- `resources/js/site/app.js` — 001 sonrası: KTComponents init + `initReveals()`. Alpine **import edilmiyor** ama `alpinejs ^3.13.3` package.json'da kurulu.
- Etiket metinleri `$siteSettings->uiLine('...')` ve `$siteSettings->localized('...')` üzerinden gelir — bu çağrıları KORU, hard-coded metinle değiştirme (Türkçe fallback'ler zaten çağrıların içinde).
- Rota yardımcıları: `\App\Support\Site\SiteLocalization::homeUrl($siteCurrentLocale)`, `SiteLocalization::switchUrl(...)`, `route('member.*', ['site_locale' => $siteCurrentLocale])` — hepsi aynen korunmalı.

## Commands you will need

| Purpose | Command | Expected on success |
|---|---|---|
| Install | `npm install` | exit 0 |
| Build | `npm run build` | exit 0 |

## Scope

**In scope**:
- `resources/views/site/layouts/main/app.blade.php`
- `resources/js/site/app.js` (yalnızca Alpine import/start eklemek)

**Out of scope**:
- Diğer tüm site görünümleri (003–005'in işi), admin dosyaları, `app.css`'in `--bs-*`/`.kt-*` blokları.
- PHP controller/model/route dosyaları — hiçbir rota veya değişken adı değişmez.

## Git workflow

- Branch: `advisor/site-redesign` (001'in devamı)
- Commit mesajı örneği: `site header ve footer yeniden tasarlandı, mobil menü eklendi`
- Push/PR yok.

## Steps

### Step 1: Alpine'ı site bundle'ına ekle

`resources/js/site/app.js` başına:

```js
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();
```

Mevcut importlardan sonra, `domReady` tanımından önce. (Site görünümlerinde henüz `x-data` yok; Alpine start güvenlidir.)

**Verify**: `npm run build` → exit 0.

### Step 2: Header'ı yeniden kur

`app.blade.php` header bölümünü (satır 34-120) şu yapıyla değiştir. Tasarım dili: tek satır, sakin, tipografi odaklı; aktif link dolu pill DEĞİL, `text-primary` + ince alt çizgi.

Yapı taslağı (Blade değişken çağrılarını mevcut koddan aynen taşı):

```blade
<a href="#site-main" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 kt-btn kt-btn-primary">İçeriğe atla</a>

<header class="sticky top-0 z-40 border-b border-border bg-background/90 backdrop-blur-xl" x-data="{ mobileOpen: false }">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 lg:h-[72px] lg:px-6">
        {{-- Logo: mevcut baş-harf karesi + site adı; tagline yalnızca lg: ekranda --}}
        {{-- Desktop nav (hidden lg:flex): linkler + Alpine dropdown --}}
        {{-- Sağ: dil seçici (Alpine dropdown), lg: aksiyon butonları, hamburger (lg:hidden) --}}
    </div>
    {{-- Mobil panel: x-show="mobileOpen" x-collapse yok, x-transition ile; nav linkleri + aksiyonlar dikey liste --}}
</header>
```

Zorunlu detaylar:
- Nav linki (desktop): `class="relative px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"`; aktif durumda `text-primary` ve altında `after:absolute after:inset-x-3 after:-bottom-px after:h-0.5 after:rounded-full after:bg-primary after:content-['']`.
- Alt menü dropdown (Alpine): `<div x-data="{ open: false }" @click.outside="open = false" class="relative">` içinde `<button @click="open = !open" :aria-expanded="open" aria-haspopup="true">`; panel: `x-show="open" x-transition.origin.top` + `class="absolute left-0 top-full z-50 mt-2 min-w-[220px] rounded-2xl border border-border bg-background p-2 shadow-lg"`, öğeler `rounded-xl px-3 py-2 text-sm`.
- Dil seçici aynı Alpine dropdown deseni, sağa hizalı panel.
- Hamburger: `<button class="lg:hidden" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-controls="site-mobile-nav" aria-label="Menü">` — ikon olarak 2 çizgili minimal SVG (span'larla `rotate-45` çapraz animasyon serbest, transform+opacity dışına çıkma).
- Mobil panel `id="site-mobile-nav"`: `x-show="mobileOpen" @click.outside="mobileOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" ...` + `class="lg:hidden border-t border-border bg-background px-4 py-4"`. İçinde: nav linkleri dikey (`grid gap-1`), alt menüler girintili düz liste (mobilde dropdown yok), ayraç, sonra aksiyon butonları (`grid gap-2`).
- `<main>` etiketine `id="site-main"` ekle (satır 122).
- Üye/ziyaretçi aksiyon mantığı ($hasActiveMemberSession dalları) ve TÜM route/uiLine çağrıları birebir korunur.

**Verify**: `npm run build` → exit 0; `grep -c "x-data" resources/views/site/layouts/main/app.blade.php` → ≥ 2; `grep -c "<details" resources/views/site/layouts/main/app.blade.php` → 0.

### Step 3: Sayfa arka planını token'lara bağla

Satır 20'deki hard-coded gradyan sarmalayıcıyı sadeleştir:

```blade
<div class="min-h-screen">
```

(Arka plan rengi artık 001'deki `body.site-shell` token'ından gelir. Radial süs gradyanı kaldırılıyor — sakin yön kararı.)

**Verify**: `grep -c "radial-gradient" resources/views/site/layouts/main/app.blade.php` → 0.

### Step 4: Footer'ı yeniden tasarla

Footer'ı (satır 126-177) şu yapıyla değiştir — içerik kaynakları (localized/uiLine/social çağrıları) aynen korunur:

- Üst blok: `border-t border-border bg-muted/40` yüzey; `max-w-7xl` içinde `grid gap-10 py-14 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)]`.
  - Sütun 1: site adı `font-display text-2xl text-foreground` + footer notu `max-w-sm text-sm leading-7 text-muted-foreground` + iletişim satırları (e-posta `mailto:`, telefon `tel:` linki olarak).
  - Sütun 2: `uppercase tracking-[0.18em] text-xs` başlık (uiLine korunur) + nav linkleri; çocuk öğeler `pl-4` yerine üstlerinin altında normal hizada, `text-sm text-muted-foreground hover:text-foreground`.
  - Sütun 3: sosyal linkler (mevcut @foreach korunur).
- Alt bar: `border-t border-border py-5` içinde `flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground`; solda `© {{ date('Y') }} {{ site adı }}`, sağda "KVKK / Kullanım" benzeri linkler YOK (rota yok) — sadece telif satırı ve varsa tagline.

**Verify**: `npm run build` → exit 0; `grep -c "date('Y')" resources/views/site/layouts/main/app.blade.php` → ≥ 1.

## Test plan

Test altyapısı yok; doğrulama build + grep kapılarıdır. Ek olarak gözle kontrol listesi (rapor NOTES'a yaz): mobil panelde tüm nav + aksiyonlar erişilebilir mi, dropdown dışarı tıklamada kapanıyor mu (kod incelemesiyle).

## Done criteria

- [ ] `npm run build` exit 0
- [ ] `grep -c "<details" resources/views/site/layouts/main/app.blade.php` → 0
- [ ] `grep -c "aria-expanded" resources/views/site/layouts/main/app.blade.php` → ≥ 2
- [ ] `grep -c "site-mobile-nav" resources/views/site/layouts/main/app.blade.php` → ≥ 2 (buton aria-controls + panel id)
- [ ] `grep -c "uiLine" resources/views/site/layouts/main/app.blade.php` → 002 öncesiyle aynı veya daha fazla (etiket çağrısı kaybolmadı; öncesinde sayıp not al)
- [ ] `git status` — in-scope dışında değişiklik yok
- [ ] `plans/README.md` güncellendi (reviewer aksini söylemediyse)

## STOP conditions

- Plan 001 uygulanmamışsa (body'de `site-shell` yoksa).
- `alpinejs` import edilince build hatası veriyorsa ve iki denemede çözülemiyorsa.
- Header'daki üye oturum mantığında ($hasActiveMemberSession) plana uymayan ek dallar varsa — mantığı DEĞİŞTİRME, aynen taşı; taşınamıyorsa STOP.

## Maintenance notes

- Yeni nav öğesi türleri (mega menü vb.) eklenirse Alpine dropdown deseni genişletilmeli.
- KTComponents init (app.js) hâlâ çalışıyor; ileride KTUI bağımlılığı sökülürse `initKtComponents` kaldırılabilir — bu plan bilerek dokunmadı.
- Reviewer: mobil panelin `x-show` başlangıç durumunda flash yapmaması için `x-cloak` gerekebilir; `[x-cloak]{display:none}` kuralı app.css'e 003'te değil burada eklenmediyse kontrol et (eklendiyse in-scope sapması olarak NOTES'ta belirtilmeli).
