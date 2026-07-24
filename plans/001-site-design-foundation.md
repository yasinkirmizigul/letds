# Plan 001: Site tasarım temelini kur — fontlar, OKLCH token katmanı, Tailwind @theme eşlemesi, motion altyapısı

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan
> in `plans/README.md` — unless a reviewer dispatched you and told you they
> maintain the index.
>
> **Drift check (run first)**: `git diff --stat 9c6da29..HEAD -- resources/css/app.css resources/views/site/ resources/js/site/ package.json`
> If any in-scope file changed since this plan was written, compare the
> "Current state" excerpts against the live code before proceeding; on a
> mismatch, treat it as a STOP condition.

## Status

- **Priority**: P1
- **Effort**: M
- **Risk**: MED
- **Depends on**: none
- **Category**: tech-debt + direction (design system)
- **Planned at**: commit `9c6da29`, 2026-07-23

## Why this matters

Herkese açık site şu an sistem fontuyla çalışıyor: `app.css:352`'de `--font-sans: 'Instrument Sans', ...` tanımlı ama bu font **hiçbir yerden yüklenmiyor** (ne Google Fonts linki ne @font-face ne npm paketi var). Ayrıca Blade görünümleri her yerde `text-foreground`, `bg-background`, `text-muted-foreground`, `border-border` gibi sınıflar kullanıyor; fakat Tailwind v4'te bu utility'ler ancak `@theme` bloğunda `--color-*` anahtarı tanımlıysa üretilir — `app.css`'teki tek `@theme` bloğu (satır 351) yalnızca font tanımlıyor. Muhtemel sonuç: bu sınıflar derlenmiyor ve ör. "muted" metinler ana metin renginde görünüyor. Bu plan, sitenin görsel seviye atlamasının zeminini kurar: gerçek yüklenen fontlar (Türkçe latin-ext destekli), rafine OKLCH mavi paleti, çalışan semantik renk utility'leri ve scroll-reveal motion altyapısı. Sonraki tüm planlar (002–005) bu temele bağımlıdır.

## Current state

- `resources/css/app.css` (2315 satır) — hem admin hem site tarafından yüklenir (`resources/views/admin/layouts/partials/head.blade.php:20` ve `resources/views/site/layouts/main/app.blade.php:12` ikisi de `@vite(['resources/css/app.css'])` içerir). **Bu yüzden `--bs-*` değişkenlerine dokunulmaz.**
- `app.css:351-354` — mevcut tek `@theme` bloğu:
  ```css
  @theme {
      --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
      'Segoe UI Symbol', 'Noto Color Emoji';
  }
  ```
- `app.css:327-349` civarı — semantik değişkenler `:root, .dark` altında tanımlı (`--background`, `--foreground`, `--border`, `--muted`, `--muted-foreground`, `--primary`, `--primary-foreground`, ayrıca `--color-primary/success/info/warning/danger` düz `:root` değişkeni olarak; `@theme` içinde DEĞİL).
- `resources/js/site/app.js` — site giriş noktası; `KTComponents.init()` çağırır ve `site-js-ready` sınıfı ekler. Motion init buraya eklenecek:
  ```js
  import '../bootstrap';
  import './auth/member-register';

  function initKtComponents() { ... }
  function domReady(fn) { ... }

  domReady(() => {
      initKtComponents();
      document.documentElement.classList.add('site-js-ready');
  });
  ```
- `resources/views/site/layouts/main/app.blade.php:15` — site body: `<body class="min-h-screen bg-background text-foreground">`
- `package.json` — deps: tailwindcss ^4.1.18, @tailwindcss/vite, alpinejs ^3.13.3 (kurulu ama site tarafında import edilmiyor), sweetalert2, fullcalendar. Font paketi yok.
- Depo kuralları: Vite girişleri `vite.config.js`'te sabit listede; Blade'de Tailwind utility sınıfları kullanılır; yorumlar/metinler Türkçe.

## Commands you will need

| Purpose | Command | Expected on success |
|---|---|---|
| Install | `npm install` | exit 0 |
| Build | `npm run build` | exit 0, `public/build/assets/*.css` üretilir |
| Utility doğrulama | `grep -c "text-foreground" public/build/assets/app-*.css` | ≥ 1 (adım 3 sonrası) |

Not: Bu makinede `vendor/` ve `.env` yok — PHP/artisan komutları ÇALIŞMAZ ve gerekmez. Doğrulama yalnızca npm üzerindendir.

## Scope

**In scope** (yalnızca bu dosyalar):
- `package.json`, `package-lock.json` (yalnızca `npm install <paket>` yoluyla)
- `resources/css/app.css`
- `resources/js/site/app.js`
- `resources/views/site/layouts/main/app.blade.php` (yalnızca body'ye `site-shell` sınıfı ekleme)

**Out of scope** (dokunma):
- `resources/views/admin/**` ve `resources/js/admin/**` — admin panel bu planın dışında.
- `app.css` içindeki `--bs-*` değişken tanımları ve `.kt-*` bileşen sınıfları — admin bunlara bağımlı. Değer DEĞİŞTİRME; yalnızca yeni blok EKLE.
- `vite.config.js` — yeni giriş dosyası gerekmiyor.
- Diğer site görünümleri — 002/003/004/005 planlarının işi.

## Git workflow

- Branch: `advisor/site-redesign` (yoksa oluştur; 002–005 aynı branch'te devam edecek)
- Her adım için ayrı commit; mesaj stili depodaki gibi kısa Türkçe (örnek: `site fontları ve tasarım tokenları eklendi`)
- Push/PR yok.

## Steps

### Step 1: Font paketlerini kur

```bash
npm install @fontsource-variable/besley @fontsource-variable/schibsted-grotesk
```

**Verify**: `npm ls @fontsource-variable/besley @fontsource-variable/schibsted-grotesk` → iki paket de listede, exit 0. Eğer paketlerden biri npm registry'de yoksa STOP.

### Step 2: Fontları app.css'e bağla

`resources/css/app.css` dosyasının EN ÜSTÜNE (`@import "tailwindcss";` satırından ÖNCE) ekle:

```css
@import "@fontsource-variable/schibsted-grotesk/index.css";
@import "@fontsource-variable/besley/index.css";
```

Sonra mevcut `@theme` bloğunu (satır ~351) şu şekilde genişlet — `--font-sans` değerini DEĞİŞTİRME (admin'i etkiler), yalnızca `--font-display` EKLE:

```css
@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji',
    'Segoe UI Symbol', 'Noto Color Emoji';
    --font-display: 'Besley Variable', Georgia, 'Times New Roman', serif;
}
```

**Verify**: `npm run build` → exit 0; `grep -c "Besley" public/build/assets/*.css` → ≥ 1; `ls public/build/assets | grep -ci "woff2"` → ≥ 1 (font dosyaları kopyalanmış).

### Step 3: Semantik renk utility'lerini çalışır hale getir (@theme eşlemesi)

Aynı `@theme` bloğuna şu satırları ekle (değerler mevcut semantik değişkenlere delege eder, böylece scope'lu override'lar çalışır):

```css
    --color-background: var(--background);
    --color-foreground: var(--foreground);
    --color-border: var(--border);
    --color-input: var(--input);
    --color-ring: var(--ring);
    --color-muted: var(--muted);
    --color-muted-foreground: var(--muted-foreground);
    --color-accent: var(--accent);
    --color-accent-foreground: var(--accent-foreground);
    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-secondary: var(--secondary);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-success: var(--bs-success);
    --color-info: var(--bs-info);
    --color-warning: var(--bs-warning);
    --color-danger: var(--bs-danger);
```

Mevcut `:root` içindeki `--color-primary: var(--bs-primary);` benzeri satırlar (app.css:343-347) olduğu gibi KALSIN — silme, çakışmazlar.

**Verify**: `npm run build` → exit 0; `grep -c "text-foreground" public/build/assets/app-*.css` → ≥ 1 VE `grep -c "text-muted-foreground" public/build/assets/app-*.css` → ≥ 1. NOT: Bu utility'ler admin görünümlerinde de kullanılıyor; bu değişiklikle admin'de daha önce etkisiz olan sınıflar da çalışmaya başlar (amaçlanan davranış — rapora not düş).

### Step 4: Site kapsamlı rafine token katmanı ekle

`app.css`'te `@layer base` bloğundan HEMEN ÖNCE yeni bir bölüm ekle. Bu değerler yalnızca `body.site-shell` altında geçerli olur — admin etkilenmez:

```css
/* =========================
   SITE THEME — rafine OKLCH katmanı (yalnızca herkese açık site)
   ========================= */
body.site-shell {
    /* Maviye tonlanmış nötrler — saf beyaz/siyah yok */
    --background: oklch(0.99 0.004 255);
    --foreground: oklch(0.22 0.035 262);
    --border: oklch(0.905 0.012 252);
    --muted: oklch(0.962 0.008 250);
    --muted-foreground: oklch(0.50 0.03 258);
    --accent: oklch(0.955 0.02 255);
    --accent-foreground: oklch(0.30 0.05 260);
    /* Marka mavisi korunur (#006AE6 ≈), OKLCH'de tanımlanır */
    --primary: oklch(0.53 0.19 258);
    --primary-foreground: oklch(0.995 0.002 255);

    font-family: 'Schibsted Grotesk Variable', ui-sans-serif, system-ui, sans-serif;
    font-optical-sizing: auto;
}

body.site-shell .font-display {
    font-family: 'Besley Variable', Georgia, serif;
}
```

Ayrıca aynı bölümde scroll-reveal ve slider crossfade için motion altyapısını ekle:

```css
body.site-shell [data-reveal] {
    opacity: 0;
    transform: translateY(16px);
    transition: opacity 0.7s cubic-bezier(0.22, 1, 0.36, 1), transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
    transition-delay: var(--reveal-delay, 0ms);
}

body.site-shell [data-reveal].is-revealed {
    opacity: 1;
    transform: none;
}

@media (prefers-reduced-motion: reduce) {
    body.site-shell [data-reveal] {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
```

`resources/views/site/layouts/main/app.blade.php:15` body etiketini güncelle:
`<body class="site-shell min-h-screen bg-background text-foreground">`

**Verify**: `npm run build` → exit 0; `grep -c "site-shell" public/build/assets/app-*.css` → ≥ 1.

### Step 5: Reveal JS'ini site girişine ekle

`resources/js/site/app.js` içindeki `domReady(...)` çağrısına reveal init ekle (mevcut yapıyı bozmadan):

```js
function initReveals() {
    const items = document.querySelectorAll('[data-reveal]');
    if (!items.length) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        items.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    items.forEach((el) => observer.observe(el));
}
```

ve `domReady` bloğunda `initKtComponents();` satırından sonra `initReveals();` çağır.

**Verify**: `npm run build` → exit 0 (site/app.js Vite girişi olduğundan derlemeye dahil).

## Test plan

Bu depoda JS/PHP test altyapısı kurulu değil (vendor yok, npm test scripti yok). Doğrulama build tabanlıdır: Done criteria'daki grep kontrolleri test yerine geçer. Yeni test dosyası YAZMA.

## Done criteria

- [ ] `npm run build` exit 0
- [ ] `grep -c "text-muted-foreground" public/build/assets/app-*.css` ≥ 1
- [ ] `grep -c "Besley" public/build/assets/app-*.css` ≥ 1
- [ ] `grep -c "site-shell" public/build/assets/app-*.css` ≥ 1
- [ ] `grep -n "site-shell" resources/views/site/layouts/main/app.blade.php` → body satırında mevcut
- [ ] `git status` — in-scope listesi dışında değişen dosya yok (public/build/ git'e girmiyorsa dokunma; .gitignore'a bak)
- [ ] `plans/README.md` durum satırı güncellendi (reviewer aksini söylemediyse)

## STOP conditions

- `@fontsource-variable/besley` veya `@fontsource-variable/schibsted-grotesk` npm'de yoksa/kurulamıyorsa.
- `app.css`'te satır 351 civarında `@theme` bloğu bu plandaki alıntıyla eşleşmiyorsa (drift).
- Adım 3 sonrası build'te `text-foreground` HÂLÂ üretilmiyorsa (Tailwind v4 yapılandırmasında beklenmedik bir şey var demektir) — iki denemeden sonra STOP.
- Herhangi bir adım `--bs-*` değişkenlerini değiştirmeyi gerektiriyor gibi görünüyorsa.

## Maintenance notes

- Bundan sonra site görünümlerinde başlıklar `font-display` sınıfını kullanmalı (planlar 002–005 uygular).
- `--reveal-delay` inline style ile stagger verilir: `style="--reveal-delay: 120ms"`.
- Admin tarafında semantik utility'lerin aktifleşmesi görsel iyileşmedir ama admin QA'sı ayrıca yapılmalı (bu plan kapsamı dışı; reviewer notu).
- İleride dark mode istenirse `body.site-shell` token bloğunun `.dark` varyantı eklenerek genişletilir.
