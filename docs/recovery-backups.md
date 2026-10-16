# PROBABLUE tam kurtarma yedeği

Bu yapı haftada bir (pazartesi 04.15, `Europe/Istanbul`) veritabanını, `storage/app/public`, `storage/app/private`, `.env` ve mevcutsa üretilmiş SEO dosyalarını (`public/sitemap.xml`, `public/robots.txt`, `public/llms.txt`) AES-256 ZIP olarak **özel S3 uyumlu bucket** içine koyar. Kod, `vendor` ve derleme dosyaları arşivde değildir; bunlar sürüm kontrolü ve dağıtım paketinden yeniden kurulur.

## Kurulum

1. Sunucuya PHP 8.4, `zip` ve `openssl` uzantıları, Composer ve veritabanına uygun döküm/geri yükleme CLI araçlarını (`mysqldump` ve `mysql` veya ilgili PostgreSQL araçları) kurun. Paket Windows sunucular için tasarlanmamıştır; üretimde Linux kullanın.
2. `.env` içine `BACKUP_ARCHIVE_PASSWORD` (en az 32 rastgele karakter), `BACKUP_AWS_ACCESS_KEY_ID`, `BACKUP_AWS_SECRET_ACCESS_KEY`, `BACKUP_AWS_BUCKET`, `BACKUP_AWS_DEFAULT_REGION` ve gerekiyorsa `BACKUP_AWS_ENDPOINT`/`BACKUP_AWS_USE_PATH_STYLE_ENDPOINT` girin. Bucket public olmasın; ayrı, yalnızca bu bucket'a erişen anahtar kullanın.
3. Parolanın ve bulut erişim bilgilerinin bir kopyasını **sunucu dışında** parola kasasında saklayın. Yedek parolası kaybolursa arşiv açılamaz. `.env` içindeki `APP_KEY`, veritabanındaki şifreli alanların açılması için geri yüklenmelidir.
4. Zamanlayıcıyı çalıştırın: `* * * * * cd /uygulama && php artisan schedule:run >> /dev/null 2>&1`. İlk yedeği `php artisan recovery:backup` ile alın. Yönetim panelinde **Kurtarma Yedekleri** sayfasından bulutta göründüğünü doğrulayın.
5. Bir arşivi indirin ve **ayrı bir test ortamında** geri yüklemeyi deneyin. Yedek varlığı tek başına kurtarılabilirlik kanıtı değildir. Bucket sürümleme ve bağımsız saklama/retention politikası etkinleştirilmesi önerilir.

`BACKUP_NAME` değiştirilirse eski arşivlerin klasörü farklı kalır. Güncel yedek adı varsayılan olarak `probablue-recovery`dir. Son 56 gün içindeki tüm arşivler tutulur; daha eski arşivler haftalık politikaya göre temizlenir. Temizleme ancak yeni yedek başarıyla oluşunca çalışır.

## Sunucu kaybından kurtarma

1. Uygulama kodunu, `vendor` bağımlılıklarını ve `public/build` varlıklarını **yedekle uyumlu sürümde** yeni Linux sunucusuna kurun. Boş bir veritabanı oluşturun; henüz migrate çalıştırmayın.
2. Geçici `.env` dosyasını yeni DB ve yedek bulutu bilgileriyle oluşturun. Aynı `BACKUP_ARCHIVE_PASSWORD` değerini kullanın; `php artisan config:clear` çalıştırın.
3. Panelden indirdiğiniz arşivi özel bucket'a tekrar yükleyebilirsiniz. Yalnızca şifresi ve yapısı doğrulanan arşivler panelde kabul edilir. Doğrudan buluta aktardıysanız `<BACKUP_NAME>/DOSYA.zip` yoluna koyun.
4. `php artisan recovery:stage DOSYA.zip` çalıştırın. Komut `.env` ve kullanıcı dosyalarını `storage/app/recovery-staging/...` içine çıkarır; hiçbir canlı dosyanın üzerine yazmaz.
5. Hazırlanan `.env` içindeki uygulama ayarlarını yeni sunucunun `.env` dosyasıyla güvenli biçimde birleştirin; özellikle **orijinal `APP_KEY`** korunmalı, yeni DB ve bulut erişim bilgileri ise yeni sunucuya uygun kalmalıdır. `php artisan config:clear` çalıştırın.
6. **Hedef DB boşken** `php artisan recovery:restore-db DOSYA.zip` çalıştırın ve terminal onayını verin. Komut dolu veritabanını reddeder; `--reset` kullanmaz. Bu komut için DB CLI aracı gereklidir.
7. Hazırlık klasöründeki `storage/app/public`, `storage/app/private` ve varsa `public` içindeki üretilmiş SEO dosyalarını aynı yollara taşıyın. `php artisan storage:link`, `php artisan config:cache` ve normal uygulama kontrollerini çalıştırın.
8. Uygulamayı açmadan önce ayrı test ortamında giriş, örnek üye dosyası, şifreli entegrasyon ayarı ve yönetim paneli kontrollerini yapın. Hazırlık klasöründeki `.env` ve kalan ZIP kopyalarını güvenli biçimde temizleyin. Başarısız DB geri yüklemesi ardından `storage/app/private/backup-restore-temp` altında geçici açık döküm kalıp kalmadığını da kontrol edin.

Canlı veritabanına panelden tek tıkla geri yükleme yoktur. Dolu veritabanında önce yeni boş DB oluşturup orada doğrulamak gerekir. ZIP biçimi özel/proprietary değildir; erişim parolasına sahip standart AES-ZIP araçları da açabilir. Güvenlik, güçlü parola ve özel bucket erişimine dayanır.
