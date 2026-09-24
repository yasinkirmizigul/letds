# PROBABLUE Kurumsal Platform

PROBABLUE; istatistiksel analiz, danışmanlık, içerik yönetimi ve operasyon süreçlerini tek merkezde birleştiren kurumsal bir web platformudur. Ziyaretçi deneyimi, üyelik işlemleri, randevu yönetimi ve yönetim paneli aynı ürün çatısı altında çalışır.

## Ürün kapsamı

- Kurumsal web sitesi ve çok dilli içerik yönetimi
- Üyelik, profil ve güvenli parola işlemleri
- Randevu oluşturma ve operasyon takibi
- Hizmet, proje, ürün, blog ve galeri yönetimi
- İletişim talepleri ve yönetici bildirimleri
- Sipariş, ödeme ve kargo süreçleri
- Rol ve yetki tabanlı yönetim paneli
- Tema, marka, SEO ve e-posta ayarları

## Teknik gereksinimler

- PHP 8.4 veya üzeri
- Composer
- Node.js ve npm
- MySQL, PostgreSQL veya SQLite

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Yerel geliştirme ortamını başlatmak için:

```bash
composer run dev
```

## Test ve kalite kontrolleri

```bash
php artisan test
npm run build
```

Kod biçimlendirme kontrolü için:

```bash
vendor/bin/pint
```

## Geçici tanıtım bağlantısı

Windows üzerinde parola korumalı geçici bir Cloudflare bağlantısı oluşturulabilir:

```powershell
winget install --id Cloudflare.cloudflared --exact
.\scripts\share-demo.cmd
```

Komut, üretim ön yüz paketini hazırlar ve geçici adres ile erişim bilgilerini terminalde gösterir. Bağlantıyı kapatmak için `Ctrl+C` kullanılabilir.

## Üretim hazırlığı

```bash
npm run build
php artisan optimize
composer install --optimize-autoloader --no-dev
```

Yayın öncesinde `.env` içindeki uygulama adı, adres, veritabanı, e-posta, kuyruk ve önbellek ayarlarının hedef ortama göre yapılandırılması gerekir.

## Proje yapısı

- `app/`: İş kuralları, modeller, denetleyiciler ve servisler
- `resources/views/`: Site ve yönetim paneli arayüzleri
- `resources/js/`: Site ve yönetim paneli istemci davranışları
- `resources/css/`: Ortak tasarım sistemi ve sayfa stilleri
- `routes/`: Site, üyelik ve yönetim rotaları
- `database/`: Migration, factory ve başlangıç verileri
- `tests/`: Birim ve özellik testleri

## Marka standardı

Varsayılan ürün adı `PROBABLUE` olarak tanımlıdır. Müşteriye veya son kullanıcıya gösterilen ekranlarda teknik altyapı, tema sağlayıcısı ya da geliştirme aracı adları kullanılmamalıdır. Görünür metinler ürünün kurumsal diliyle yazılmalıdır.
