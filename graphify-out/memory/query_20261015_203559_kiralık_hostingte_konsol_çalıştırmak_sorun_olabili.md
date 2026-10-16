---
type: "query"
date: "2026-10-15T20:35:59.533479+00:00"
question: "Kiralık hostingte konsol çalıştırmak sorun olabilir mi; mevcut kurtarma yedeklemesi buna bağlı mı?"
contributor: "graphify"
outcome: "useful"
source_nodes: ["RecoveryBackupCommand", "RecoveryStageCommand", "RecoveryRestoreDatabaseCommand"]
---

# Q: Kiralık hostingte konsol çalıştırmak sorun olabilir mi; mevcut kurtarma yedeklemesi buna bağlı mı?

## Answer

Expanded via graph vocabulary: Recovery Backup Restore Stage. Mevcut recovery:backup haftalık Artisan zamanlayıcısı ve Spatie backup:run komutuna; recovery:stage ile recovery:restore-db komutları da konsola bağlıdır. MySQL tam dökümü için mysqldump/mysql gereklidir. Host SSH/cron/PHP 8.4/ZIP/DB CLI erişimi doğrulanmadan bu çözüm kiralık hostinge dağıtılmamalıdır.

## Outcome

- Signal: useful

## Source Nodes

- RecoveryBackupCommand
- RecoveryStageCommand
- RecoveryRestoreDatabaseCommand