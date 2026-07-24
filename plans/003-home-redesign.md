# Plan 003: Ana sayfayı yeniden tasarla — admin verisi sızıntısını kapat, editoryal bölüm ritmi, crossfade slider, scroll-reveal

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan
> in `plans/README.md` — unless a reviewer dispatched you and told you they
> maintain the index.
>
> **Drift check (run first)**: `git diff --stat 9c6da29..HEAD -- resources/views/site/cms/home.blade.php resources/js/site/cms.js`
> Bu iki dosyada 001/002 dışı bir değişiklik varsa alıntıları karşılaştır; uyuşmazlıkta STOP.

## Status

- **Priority**: P1
- **Effort**: M
- **Risk**: MED
- **Depends on**: plans/001-site-design-foundation.md, plans/002-header-nav-footer.md
- **Category**: bug (veri sızıntısı) + direction (design)
- **Planned at**: commit `9c6da29`, 2026-07-23

## Why this matters

İki sorun var. **Bug:** ana sayfa hero slider'ı, her slaytta yalnızca admin'i ilgilendiren iç verileri herkese açık gösteriyor — `home.blade.php:40-46`'daki kart "Tema: <tema adı>" ve "Görsel odak: 50 / 50" (crop koordinatları) yazıyor. **Tasarım:** sayfanın tamamı aynı desenin tekrarı (beyaz `rounded-[28px]` kart + uppercase kicker + başlık); slider geçişi animasyonsuz `hidden` toggle'ı; sayaçlar ikon-kart grid'i; hiçbir giriş animasyonu yok. Ana sayfa bu CMS'in vitrinidir — bu plan onu tipografi odaklı, ritimli, hareketli bir yüze çevirir.

## Current state

- `resources/views/site/cms/home.blade.php` (186 satır) — bölümler sırasıyla: hero_notice kartı → slider → sayaçlar (`$globalCounters`) → öne çıkan sayfalar (`$featuredPages`) → SSS (`$globalFaqs`) → iletişim + harita.
  - Satır 12-65: slider. Slaytlar `{{ $loop->first ? '' : 'hidden' }} relative min-h-[520px]` div'leri; JS `hidden` sınıfını toggle'lar. Sağ altta prev/next (`ki-outline ki-left/right` ikonları), sol altta indikatörler (`data-home-slide-indicator`).
  - Satır 40-46: **silinecek sızıntı kartı**:
    ```blade
    <div class="flex items-end justify-end">
        <div class="rounded-[28px] border border-white/10 bg-white/10 p-5 backdrop-blur">
            <div class="text-xs uppercase tracking-[0.24em] text-white/60">Tema</div>
            <div class="mt-2 text-lg font-semibold">{{ \App\Models\Site\HomeSlider::themeOptions()[$slider->theme] ?? $slider->theme }}</div>
            <div class="mt-4 text-sm text-white/70">Görsel odak: {{ number_format($slider->crop_x, 0) }} / {{ number_format($slider->crop_y, 0) }}</div>
        </div>
    </div>
    ```
  - Satır 68-87: sayaçlar — `md:grid-cols-2 xl:grid-cols-4` ikon-kart grid'i, `data-countup-value` span'ları (JS countup buna bağlı — attribute KORUNUR).
  - Satır 89-114: öne çıkan sayfalar — 3'lü eş kart grid'i (`ki-*` ikon + slug rozeti + başlık + özet). `$page->publicUrl($siteCurrentLocale)`, `$page->localized('title')`, `$page->excerptPreview(150)` çağrıları mevcut.
  - Satır 116-137: SSS — `<details>` kartları.
  - Satır 139-179: iletişim kartı + `map_embed_url` iframe'i.
- `resources/js/site/cms.js` — `initCountups()` (IntersectionObserver + rAF, `data-countup-value`) ve `initHeroSlider()`: `slide.classList.toggle('hidden', ...)` ile anlık geçiş; indikatörlerde `bg-white`/`bg-white/35` toggle; 5500ms autoplay; prev/next/indicator tıklamaları timer'ı resetler.
- Veri sözleşmesi (KORUNACAK attribute'lar): `data-home-slider`, `data-home-slide`, `data-home-slider-prev`, `data-home-slider-next`, `data-home-slide-indicator`, `data-countup-value`.
- 001 sonrası kullanılabilir: `font-display` (Besley), `[data-reveal]` + `--reveal-delay`, rafine token'lar.

## Commands you will need

| Purpose | Command | Expected on success |
|---|---|---|
| Install | `npm install` | exit 0 |
| Build | `npm run build` | exit 0 |

## Scope

**In scope**:
- `resources/views/site/cms/home.blade.php`
- `resources/js/site/cms.js`
- `resources/css/app.css` — YALNIZCA `body.site-shell` bölümüne slider crossfade sınıfları eklemek

**Out of scope**:
- `app/Models/**`, controller'lar, `site/layouts/main/app.blade.php` (002 bitirdi), diğer görünümler, admin.

## Git workflow

- Branch: `advisor/site-redesign`
- Commit mesajı örneği: `ana sayfa yeniden tasarlandı, slider sızıntısı kaldırıldı`
- Push/PR yok.

## Steps

### Step 1: Sızıntı kartını kaldır, hero'yu tipografi odaklı kur

- Satır 40-46'daki "Tema / Görsel odak" kolonunu tamamen sil; grid'i tek kolona indir (`lg:grid-cols-[minmax(0,1fr)_280px]` kaldır).
- Başlığı `font-display`'e çevir: `<h1 class="max-w-3xl font-display text-4xl font-semibold leading-[1.1] md:text-6xl">`.
- Badge, subtitle, body, CTA blokları içerik olarak korunur; CTA'nın yanına ikinci sakin aksiyon ekle: iletişim sayfasına `text-white/80 hover:text-white` metin linki (`route('site.contact-messages.create', ['site_locale' => $siteCurrentLocale])`, etiket: `$siteSettings->uiLine('nav_contact_label')`).
- Slaytların köşe yarıçapını `rounded-[36px]`'ten `rounded-3xl`e indir (sistemle tutarlılık), min yükseklik `min-h-[560px] lg:min-h-[600px]`.

**Verify**: `grep -c "crop_x\|themeOptions" resources/views/site/cms/home.blade.php` → 0.

### Step 2: Slider'ı crossfade yap

- Blade: her slayt `hidden` yerine üst üste binecek şekilde: ilk slayt `relative`, diğerleri `absolute inset-0`; hepsine `data-home-slide` kalır, aktif olmayanlara `opacity-0 pointer-events-none` başlangıcı. Kapsayıcıya `relative` zaten var.
- `app.css`'te `body.site-shell` bölümüne ekle:
  ```css
  body.site-shell [data-home-slide] {
      transition: opacity 0.6s ease;
  }
  @media (prefers-reduced-motion: reduce) {
      body.site-shell [data-home-slide] { transition: none; }
  }
  ```
- `cms.js` `render()` fonksiyonunu güncelle: `hidden` toggle yerine:
  ```js
  slides.forEach((slide, slideIndex) => {
      const active = slideIndex === index;
      slide.classList.toggle('opacity-0', !active);
      slide.classList.toggle('pointer-events-none', !active);
      slide.setAttribute('aria-hidden', active ? 'false' : 'true');
  });
  ```
- Autoplay'e hover'da durma ekle: `root.addEventListener('mouseenter', () => window.clearInterval(timer))` ve `mouseleave`'de `start()`.
- Prev/next butonlarına `aria-label` ekle (Blade): "Önceki slayt" / "Sonraki slayt"; indikatörlere `aria-label="Slayt {{ $loop->iteration }}"`.

**Verify**: `npm run build` → exit 0; `grep -c "hidden" resources/js/site/cms.js` → 0.

### Step 3: Sayaçları stat bandına çevir

İkon-kart grid'ini (satır 68-87) tek yüzeyli bir banda dönüştür:

```blade
<section class="mt-16" data-reveal>
    <div class="grid gap-y-10 border-y border-border py-10 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($globalCounters as $counter)
            <div class="px-2 sm:px-6 {{ $loop->first ? '' : 'sm:border-l sm:border-border' }}">
                <div class="font-display text-5xl font-medium tracking-tight text-foreground">
                    {{ $counter->localized('prefix') }}<span data-countup-value="{{ $counter->value }}">0</span>{{ $counter->localized('suffix') }}
                </div>
                <div class="mt-3 text-sm font-medium text-foreground">{{ $counter->localized('label') }}</div>
                @if($counter->localized('description'))
                    <div class="mt-1 text-sm leading-6 text-muted-foreground">{{ $counter->localized('description') }}</div>
                @endif
            </div>
        @endforeach
    </div>
</section>
```

Not: `sm:border-l` burada ayraç çizgisidir (1px, sütun ayırıcı) — kart üstünde renkli vurgu şeridi DEĞİL; serbesttir. İkonlar bilinçli olarak kaldırıldı.

**Verify**: `grep -c "data-countup-value" resources/views/site/cms/home.blade.php` → sayaç döngüsünde hâlâ ≥ 1.

### Step 4: Öne çıkan sayfaları editoryal listeye çevir

Eş kart grid'ini (satır 89-114) numaralı editoryal satır listesine dönüştür:

- Bölüm başlığı: kicker `text-xs font-semibold uppercase tracking-[0.18em] text-primary` + `h2 class="mt-3 font-display text-3xl md:text-4xl font-semibold"`.
- Liste: `<div class="mt-8 grid">` içinde her sayfa bir satır:
  ```blade
  <a href="{{ $page->publicUrl($siteCurrentLocale) }}" data-reveal style="--reveal-delay: {{ ($loop->index % 6) * 80 }}ms"
     class="group grid gap-4 border-t border-border py-8 transition-colors hover:bg-muted/40 sm:grid-cols-[64px_minmax(0,1fr)_auto] sm:items-baseline sm:gap-8 sm:px-4 last:border-b">
      <div class="font-display text-sm text-muted-foreground">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
      <div>
          <h3 class="font-display text-2xl font-semibold text-foreground transition-colors group-hover:text-primary">{{ $page->localized('title') }}</h3>
          <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">{{ $page->excerptPreview(150) }}</p>
      </div>
      <div class="text-sm font-medium text-primary opacity-0 transition-opacity group-hover:opacity-100">{{ $siteSettings->uiLine('home_featured_cta_label') }} →</div>
  </a>
  ```
- `ki-*` ikon kutusu ve `/slug` rozeti kaldırılır.

**Verify**: `grep -c "excerptPreview" resources/views/site/cms/home.blade.php` → ≥ 1; `grep -c "ki-abstract-26" resources/views/site/cms/home.blade.php` → 0.

### Step 5: SSS ve iletişim bölümlerini rafine et

- SSS: kart `<details>` yerine `divide-y divide-border border-y border-border` liste; her öğe:
  ```blade
  <details class="group py-5">
      <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-semibold text-foreground">
          {{ $faq->localized('question') }}
          <span class="text-xl font-light text-muted-foreground transition-transform duration-300 group-open:rotate-45">+</span>
      </summary>
      <div class="mt-3 max-w-2xl text-sm leading-7 text-muted-foreground">{!! nl2br(e($faq->localized('answer'))) !!}</div>
  </details>
  ```
  Sol kolon (kicker + başlık + açıklama) korunur, başlık `font-display`.
- İletişim bölümü: kart yerine `rounded-3xl bg-foreground text-background` koyu kapanış paneli (tek güçlü vurgu bölümü): başlık `font-display text-3xl`, iletişim satırları `text-background/70`, CTA'lar mevcut route'larla (`kt-btn kt-btn-primary` + beyaz metin linki). Harita iframe'i `rounded-3xl` ile yanında kalır (map_embed_url koşulu korunur).
- Tüm ana bölümlere `data-reveal` ekle (hero hariç).

**Verify**: `npm run build` → exit 0; `grep -c "data-reveal" resources/views/site/cms/home.blade.php` → ≥ 4.

## Test plan

Test altyapısı yok. Kod-incelemesi kontrol listesi (NOTES'a): slider veri attribute sözleşmesi bozulmadı; `hero_notice`, `$sliders`, `$globalCounters`, `$featuredPages`, `$globalFaqs`, `$siteSettings` dallarının hepsi boş-koleksiyon durumunda hata vermiyor (mevcut `isNotEmpty()` koşulları korunmuş olmalı).

## Done criteria

- [ ] `npm run build` exit 0
- [ ] `grep -c "crop_x\|themeOptions" resources/views/site/cms/home.blade.php` → 0
- [ ] `grep -c "font-display" resources/views/site/cms/home.blade.php` → ≥ 4
- [ ] `grep -c "data-reveal" resources/views/site/cms/home.blade.php` → ≥ 4
- [ ] `grep -rn "border-l-\[2-9]\|border-l-2\|border-l-4\|border-r-2\|border-r-4" resources/views/site/cms/home.blade.php` → 0 eşleşme (yasaklı vurgu şeridi deseni yok; `sm:border-l` 1px ayraç serbest)
- [ ] `git status` — in-scope dışı değişiklik yok
- [ ] `plans/README.md` güncellendi (reviewer aksini söylemediyse)

## STOP conditions

- `home.blade.php`'deki bölüm yapısı "Current state"teki sırayla eşleşmiyorsa.
- Slider JS sözleşmesindeki bir attribute'u değiştirmek zorunda kalıyorsan (yeniden adlandırma yasak).
- `$slider`, `$counter`, `$page`, `$faq` modellerinde plana gereken bir metot yoksa (ör. `excerptPreview`).

## Maintenance notes

- Slider'a slayt sayısı 1 iken `initHeroSlider` erken çıkar (`slides.length <= 1`) — tek slaytta ilk slayt `relative` + görünür olduğundan sorun yok; yeni slayt ekleme akışında admin tarafı değişmedi.
- Koyu iletişim paneli sitenin tek koyu bölümüdür; başka bölümleri koyulaştırmadan önce 60-30-10 dengesini koru.
- İleride blog/ürün vitrini ana sayfaya eklenirse editoryal satır deseni (Step 4) yeniden kullanılmalı.
