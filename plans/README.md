# Implementation Plans

improve skill tarafından 2026-07-23'te üretildi (commit `9c6da29`). Odak: **herkese açık site ön yüzünü seviye atlatmak**. Aşağıdaki sırayla uygulanır. Her executor: planı baştan sona oku, STOP koşullarına uy, bitince satırını güncelle.

Tasarım bağlamı: proje kökündeki `.impeccable.md` (sakin/güvenilir/özenli, açık tema, mavi korunur, Besley + Schibsted Grotesk).

## Execution order & status

| Plan | Title | Priority | Effort | Depends on | Status |
|------|-------|----------|--------|------------|--------|
| 001 | Site tasarım temeli (fontlar, OKLCH tokenlar, @theme, motion) | P1 | M | — | DONE (worktree `advisor/site-redesign` @ 0c5486c, reviewer onaylı) |
| 002 | Header/nav/footer + mobil menü | P1 | M | 001 | DONE (@ 7f9b6e2, reviewer onaylı) |
| 003 | Ana sayfa yeniden tasarımı + slider sızıntı fix | P1 | M | 001, 002 | DONE (@ 1a9d7db, reviewer onaylı; not: cms.js'te aria-hidden IDL reflection `slide.ariaHidden` ile yazıldı — planın grep kapısı literal "hidden"i yasakladığı için) |
| 004 | Randevu deneyimi yeniden tasarımı | P2 | M | 001 | DONE (@ 81a6661 + revizyon 9f87c58, reviewer onaylı) |
| 005 | İkincil sayfalar hizalaması + SEO skoru sızıntı fix | P2 | L | 001 | DONE (@ a033ec5·2a3d3b3·0f6de6d·fbcca76, reviewer onaylı) |

**Durum: 5/5 plan tamamlandı ve `master`'a fast-forward ile alındı (HEAD `fbcca76`, push YOK). Commit'lerde Claude ibaresi yok.**

Status values: TODO | IN PROGRESS | DONE | BLOCKED (tek satır neden) | REJECTED (tek satır gerekçe)

## Dependency notes

- 002–005 hepsi 001'in `@theme` eşlemesine, `site-shell` token katmanına, `font-display` ve `[data-reveal]` altyapısına dayanır — 001 DONE olmadan başlamayın.
- 003, 002'nin layout değişikliklerinin üstüne gelir (aynı branch: `advisor/site-redesign`).
- 004 ve 005 birbirinden bağımsızdır; 001 sonrası paralel yürütülebilir.
- Bu makinede `vendor/` ve `.env` YOK: PHP/artisan çalışmaz; tüm doğrulama `npm install` + `npm run build` + grep kapılarıdır.

## Bulgular — tabloya girmeyen notlar

- **Tailwind semantik utility şüphesi**: `text-foreground` vb. sınıfların hiç derlenmediği yönünde güçlü işaret var (tek `@theme` bloğu yalnız font tanımlıyor; build çıktısı da mevcut değildi, doğrulanamadı). 001 Step 3 bunu kesin çözer; executor build çıktısında öncesi/sonrası doğrulamalı.
- **Admin yan etkisi**: 001'in `@theme` eşlemesi admin görünümlerindeki (91 dosya) aynı sınıfları da aktifleştirir — amaçlanan yönde bir düzelme ama admin QA'sı ayrıca yapılmalı.

## Findings considered and rejected

- **FullCalendar site tarafında kullanılmıyor sanısı**: site randevu takvimi custom JS grid; FullCalendar yalnızca admin'de. Site planları FullCalendar temasına girmiyor (gereksiz).
- **`kt-btn`/`kt-input` sisteminin komple değişimi**: admin ile paylaşılan bileşen katmanı; söküp yeniden yazmak yüksek risk/düşük getiri. Site scope'unda token override yeterli.
- **Dark mode eklenmesi**: içerik odaklı, gündüz kullanılan herkese açık site için açık tema doğru varsayılan; kullanıcı da istemedi. Token katmanı ileride genişlemeye açık bırakıldı.
- **E-posta şablonlarının yeniden tasarımı**: farklı CSS kısıtları; ayrı bir çalışma olarak ertelendi (005 maintenance notunda kayıtlı).
