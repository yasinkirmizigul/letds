<?php

use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;

return [
    'backup' => [
        'name' => env('BACKUP_NAME', 'probablue-recovery'),
        'source' => [
            'files' => [
                'include' => [
                    base_path('.env'),
                    storage_path('app/public'),
                    storage_path('app/private'),
                    public_path('sitemap.xml'),
                    public_path('robots.txt'),
                    public_path('llms.txt'),
                ],
                'exclude' => [
                    storage_path('app/private/backup-restore-temp'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ],
            'databases' => [env('DB_CONNECTION', 'mysql')],
        ],
        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',
        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => ['recovery_backups'],
            'continue_on_failure' => false,
        ],
        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'aes256',
        'verify_backup' => true,
        'tries' => 1,
        'retry_delay' => 0,
    ],
    'notifications' => [
        'notifications' => [],
    ],
    'log_channel' => null,
    'monitor_backups' => [
        [
            'name' => env('BACKUP_NAME', 'probablue-recovery'),
            'disks' => ['recovery_backups'],
            'health_checks' => [MaximumAgeInDays::class => 8],
        ],
    ],
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => 56,
            'keep_daily_backups_for_days' => 0,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],
        'tries' => 1,
        'retry_delay' => 0,
    ],
];
