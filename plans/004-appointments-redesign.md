# Plan 004: Randevu deneyimini yeniden tasarla — adımlı akış hissi, takvim/slot durum stilleri, hafta günü başlıkları

> **Executor instructions**: Follow this plan step by step. Run every
> verification command and confirm the expected result before moving to the
> next step. If anything in the "STOP conditions" section occurs, stop and
> report — do not improvise. When done, update the status row for this plan
> in `plans/README.md` — unless a reviewer dispatched you and told you they
> maintain the index.
>
> **Drift check (run first)**: `git diff --stat 9c6da29..HEAD -- resources/views/site/appointments/index.blade.php resources/js/site/appointments/index.js resources/css/app.css`
> `app.css`'te 001/003'ün eklediği `body.site-shell` blokları beklenen değişikliktir; onun dışındaki uyuşmazlık STOP nedenidir.

## Status

- **Priority**: P2
- **Effort**: M
- **Risk**: MED
- **Depends on**: plans/001-site-design-foundation.md
- **Category**: direction (UX/design)
- **Planned at**: commit `9c6da29`, 2026-07-23

## Why this matters

Randevu almak bu sitenin ana işlevi, ama sayfası en az tasarlanmış sayfa: başlık bloğu ayrı bir kart, akışın "kişi seç → gün seç → saat seç" olduğu görsel olarak anlatılmıyor, takvim grid'inde hafta günü başlıkları yok (kullanıcı hangi sütunun Pazartesi olduğunu bilemiyor), slot/gün durum stilleri (`.app-calendar-day`, `.app-slot-button`) admin görünümüyle ortak soluk stiller. Bu plan akışı tek yüzeyde adımlandırır, takvimi okunur ve durumları belirgin hale getirir.

## Current state

- `resources/views/site/appointments/index.blade.php` (107 satır):
  - Satır 12-23: başlık kartı — `app-shell-surface` + `dashboard-kicker` "Randevu" + h1 "Randevu Oluştur" + `#calendarTitle` (JS ay adını yazar).
  - Satır 25-49: `$activeAppointment` varsa "Aktif randevun var" kartı (`app-surface-card--success`) + `#cancelBtn` / `#rescheduleBtn`; ardından `window.__HAS_ACTIVE_APPOINTMENT__` vb. global'leri set eden script blokları (KORUNUR).
  - Satır 51-101: `#booking-panel` — `#reschedule-mode-banner`, `#provider` select, `#prevMonthBtn`/`#nextMonthBtn`, `#calendar` (JS doldurur), `#date` (readonly picker), `#slots` grid, `#slot-empty`.
- `resources/js/site/appointments/index.js` (422 satır):
  - `renderCalendar()` `#calendar` içine `grid grid-cols-7 gap-2` HTML'i basar; gün hücreleri `class="calendar-day app-calendar-day is-available|is-disabled"`, uygun günlerde `freeCount` küçük sayısı. **Hafta günü başlık satırı yok.** İlk gün hesabı: `new Date(year, month, 1).getDay() || 7` (Pazartesi başlangıçlı).
  - Slot butonları: `el.className = 'app-slot-button text-center'`, seçilince `.is-selected`.
  - Yükleme durumları: `container.innerHTML = '<div class="text-sm text-muted-foreground">Takvim yükleniyor...</div>'` benzeri düz metinler.
- `resources/css/app.css:813-860` — `.app-calendar-day`, `.app-slot-button` ve durum sınıfları; `:1210+` — `.app-shell-surface`, `.app-surface-card` (bu yüzeyler admin/iletişim sayfasıyla ORTAK — global değerlerini değiştirme, `body.site-shell` altında override et).
- Kimlik sözleşmesi (İSİM DEĞİŞTİRME YASAK): `#provider`, `#calendar`, `#calendarTitle`, `#date`, `#slots`, `#slot-empty`, `#booking-panel`, `#reschedule-mode-banner`, `#active-appointment-card`, `#cancelBtn`, `#rescheduleBtn`, `#prevMonthBtn`, `#nextMonthBtn`, `window.__HAS_ACTIVE_APPOINTMENT__`, `window.__ACTIVE_APPOINTMENT_ID__`, `window.__RESCHEDULE_MODE__`, `data-app-date-picker` attribute'ları.

## Commands you will need

| Purpose | Command | Expected on success |
|---|---|---|
| Install | `npm install` | exit 0 |
| Build | `npm run build` | exit 0 |

## Scope

**In scope**:
- `resources/views/site/appointments/index.blade.php`
- `resources/js/site/appointments/index.js`
- `resources/css/app.css` — YALNIZCA `body.site-shell` scope'lu ekleme/override

**Out of scope**:
- Randevu backend'i (controller, model, route), admin randevu takvimi (`resources/js/admin/pages/appointments/**`), `.app-*` sınıflarının global tanımları, diğer görünümler.

## Git workflow

- Branch: `advisor/site-redesign`
- Commit mesajı örneği: `randevu sayfası yeniden tasarlandı`
- Push/PR yok.

## Steps

### Step 1: Sayfa iskeletini tek akışa çevir

`index.blade.php`'de başlık kartını ve booking panelini birleşik bir düzene çevir:

- Üst blok (kart DEĞİL, çıplak tipografi): kicker `text-xs font-semibold uppercase tracking-[0.18em] text-primary` ("Randevu" — mevcut metin), `h1 class="mt-3 font-display text-3xl font-semibold tracking-tight lg:text-4xl"`, altında kısa açıklama `text-sm text-muted-foreground`: "Kişiyi seç, uygun bir gün ve saat belirle — hepsi bu."
- `#calendarTitle` booking paneli içinde ay gezinme satırına taşınır: `#prevMonthBtn` — `#calendarTitle` — `#nextMonthBtn` tek satırda (`flex items-center justify-between`), butonlar `kt-btn kt-btn-outline` yuvarlak ikon butonlar (`aria-label="Önceki ay"` / `"Sonraki ay"`).
- Aktif randevu kartı: `rounded-3xl border border-success/30 bg-success/5 p-6` yüzey; "Aktif randevun var" etiketi küçük, tarih `font-display text-2xl`; butonlar aynı id'lerle kalır.
- `#booking-panel`: `rounded-3xl border border-border bg-background p-5 lg:p-8` tek yüzey. İçinde üç adım bölgesi, her birinin önünde numara rozeti (`inline-flex size-6 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary`): "1 Kişi", "2 Gün", "3 Saat". Kişi satırı: select + ay gezinme; Gün: takvim; Saat: `#date` + `#slots`.
- Tüm mevcut id/attribute'lar ve script blokları aynen korunur.

**Verify**: `npm run build` → exit 0; `for id in provider calendar calendarTitle date slots slot-empty booking-panel reschedule-mode-banner cancelBtn rescheduleBtn prevMonthBtn nextMonthBtn; do grep -q "id=\"$id\"" resources/views/site/appointments/index.blade.php || echo "EKSIK: $id"; done` → hiçbir EKSIK satırı yok (calendarTitle dahil).

### Step 2: Takvime hafta günü başlıkları ekle

`index.js` `renderCalendar()` içinde, gün hücrelerinden önce başlık satırı bas:

```js
const weekdays = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
let html = `<div class="grid grid-cols-7 gap-2">`;
html += weekdays
    .map((d) => `<div class="pb-1 text-center text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">${d}</div>`)
    .join('');
```

(İlk gün ofset hesabı Pazartesi başlangıçlı — mevcut `getDay() || 7` mantığıyla uyumlu, DEĞİŞTİRME.)

**Verify**: `grep -c "Pzt" resources/js/site/appointments/index.js` → ≥ 1; `npm run build` → exit 0.

### Step 3: Gün ve slot durum stillerini belirginleştir (site scope)

`app.css`'te `body.site-shell` bölümüne override'lar ekle (global `.app-calendar-day` tanımlarına DOKUNMA):

```css
body.site-shell .app-calendar-day {
    aspect-ratio: 1 / 1;
    border-radius: 0.75rem;
    font-variant-numeric: tabular-nums;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
body.site-shell .app-calendar-day.is-available {
    border: 1px solid var(--border);
    background: var(--background);
}
body.site-shell .app-calendar-day.is-available:hover {
    border-color: var(--primary);
    background: color-mix(in oklab, var(--primary) 6%, var(--background));
}
body.site-shell .app-calendar-day.is-disabled {
    opacity: 0.35;
    background: transparent;
}
body.site-shell .app-calendar-day.is-selected {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--primary-foreground);
}
body.site-shell .app-calendar-day.is-selected .text-muted-foreground {
    color: color-mix(in oklab, var(--primary-foreground) 75%, transparent);
}
body.site-shell .app-slot-button {
    border: 1px solid var(--border);
    border-radius: 9999px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    font-variant-numeric: tabular-nums;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
}
body.site-shell .app-slot-button:hover {
    border-color: var(--primary);
    color: var(--primary);
}
body.site-shell .app-slot-button.is-selected {
    background: var(--primary);
    border-color: var(--primary);
    color: var(--primary-foreground);
}
```

**Verify**: `npm run build` → exit 0; `grep -c "site-shell .app-slot-button" public/build/assets/app-*.css` → ≥ 1 (minify farklılıklarında `grep -c "app-slot-button" public/build/assets/app-*.css` ≥ 2 yeterli).

### Step 4: Yükleme/boş durumları iskelete çevir

`index.js`'te üç düz metin durumu iyileştir:

- Takvim yüklenirken: `container.innerHTML` → 7 sütunluk, `35` adet `<div class="app-skeleton aspect-square rounded-xl"></div>` hücreli grid.
- Saatler yüklenirken: `container.innerHTML` → 6 adet `<div class="app-skeleton h-9 w-full rounded-full"></div>`.
- `app.css` `body.site-shell` bölümüne:
  ```css
  body.site-shell .app-skeleton {
      background: color-mix(in oklab, var(--foreground) 6%, var(--background));
      animation: site-skeleton-pulse 1.4s ease-in-out infinite;
  }
  @keyframes site-skeleton-pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.55; }
  }
  @media (prefers-reduced-motion: reduce) {
      body.site-shell .app-skeleton { animation: none; }
  }
  ```
- Hata metinleri (`Takvim yüklenemedi.` / `Saatler yüklenemedi.`) korunur ama `text-red-600` yerine `text-danger` kullan.
- `#slot-empty` boş durumu Blade'de küçük bir açıklamaya dönüştür: ikon YOK, `text-sm text-muted-foreground` + "Bu gün için uygun saat bulunamadı. Başka bir gün seçmeyi dene." metni.

**Verify**: `npm run build` → exit 0; `grep -c "app-skeleton" resources/js/site/appointments/index.js` → ≥ 2; `grep -c "text-red-600" resources/js/site/appointments/index.js` → 0.

## Test plan

Test altyapısı yok. Kod incelemesi kontrolü (NOTES'a): id sözleşmesi listesindeki her öğe hem Blade'de hem JS'te değişmeden duruyor; `selectDate`/`selectSlot` akışındaki class toggle'ları (`is-selected`) yeni CSS ile eşleşiyor.

## Done criteria

- [ ] `npm run build` exit 0
- [ ] Step 1'deki id döngüsü hiçbir `EKSIK:` satırı üretmiyor
- [ ] `grep -c "Pzt" resources/js/site/appointments/index.js` ≥ 1
- [ ] `grep -c "app-skeleton" resources/js/site/appointments/index.js` ≥ 2
- [ ] `grep -c "font-display" resources/views/site/appointments/index.blade.php` ≥ 2
- [ ] `git status` — in-scope dışı değişiklik yok
- [ ] `plans/README.md` güncellendi (reviewer aksini söylemediyse)

## STOP conditions

- `index.js`'teki fonksiyon yapısı "Current state" özetiyle eşleşmiyorsa (örn. `renderCalendar` yoksa).
- Bir id'yi yeniden adlandırmadan düzen kurulamıyorsa — kurulamıyorsa yapıyı id'lere uydur, id'leri yapıya değil; o da olmuyorsa STOP.
- `.app-calendar-day` global tanımını değiştirmeden istenen görünüm elde edilemiyorsa (override özgüllüğü yetmiyorsa) — `body.site-shell` özgüllüğü yeterli olmalı; değilse STOP.

## Maintenance notes

- Backend'e yeni slot durumları eklenirse (örn. "az kaldı") `.app-slot-button` için yeni durum sınıfı `body.site-shell` bloğuna eklenmeli.
- Hafta günü kısaltmaları şu an TR hard-coded; site çok dilliyse ileride `siteSettings->uiLine` benzeri bir kaynaktan gelmesi gerekir (bilinçli erteleme).
- Reviewer: mobilde takvim hücre boyutlarını (7 sütun × aspect-square, dar ekran) kontrol et.
