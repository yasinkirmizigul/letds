<?php

namespace Tests\Feature;

use App\Models\Admin\User\Role;
use App\Models\Admin\User\User;
use App\Support\Recovery\RecoveryArchives;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Spatie\Backup\Config\Config as SpatieBackupConfig;
use Tests\TestCase;
use ZipArchive;

class RecoveryBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_unconfigured_backup_fails_closed(): void
    {
        config()->set('backup.backup.password', null);
        config()->set('filesystems.disks.recovery_backups.key', null);

        $this->artisan('recovery:backup')->assertFailed();
    }

    public function test_import_validator_accepts_only_aes_256_full_archives(): void
    {
        config()->set('backup.backup.password', str_repeat('a', 32));
        $archives = app(RecoveryArchives::class);
        $encrypted = $this->zip(true);
        $unencrypted = $this->zip(false);

        try {
            $archives->validateArchive($encrypted);
            $this->expectException(RuntimeException::class);
            $archives->validateArchive($unencrypted);
        } finally {
            unlink($encrypted);
            unlink($unencrypted);
        }
    }

    public function test_only_superadmin_can_open_recovery_panel(): void
    {
        $adminRole = Role::query()->create(['name' => 'Admin', 'slug' => 'admin']);
        $superRole = Role::query()->create(['name' => 'Super Admin', 'slug' => 'superadmin']);
        $admin = User::query()->create([
            'name' => 'Panel Admin', 'email' => 'backup-admin@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole);
        $super = User::query()->create([
            'name' => 'Super Admin', 'email' => 'backup-super@example.test',
            'password' => 'password', 'is_active' => true,
        ]);
        $super->roles()->attach($superRole);

        $this->actingAs($admin)->get(route('admin.recovery-backups.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.recovery-backups.download', ['file' => 'backup.zip']))->assertForbidden();
        $this->actingAs($super)->get(route('admin.recovery-backups.index'))
            ->assertOk()->assertSee('Kurtarma yedekleri');
    }

    public function test_valid_archive_can_be_imported_to_private_disk(): void
    {
        Storage::fake('recovery_backups');
        config()->set('backup.backup.password', str_repeat('a', 32));
        config()->set('filesystems.disks.recovery_backups.key', 'test-key');
        config()->set('filesystems.disks.recovery_backups.secret', 'test-secret');
        config()->set('filesystems.disks.recovery_backups.bucket', 'private-test');

        $zip = $this->zip(true);
        try {
            $filename = app(RecoveryArchives::class)->import(
                new UploadedFile($zip, 'test.zip', 'application/zip', null, true),
                app(\App\Support\Recovery\RecoveryConfiguration::class)
            );

            Storage::disk('recovery_backups')->assertExists('probablue-recovery/'.$filename);
            $this->assertCount(1, app(RecoveryArchives::class)->all());
        } finally {
            unlink($zip);
        }
    }

    public function test_restore_refuses_a_database_with_existing_tables(): void
    {
        config()->set('backup.backup.password', str_repeat('a', 32));
        config()->set('filesystems.disks.recovery_backups.key', 'test-key');
        config()->set('filesystems.disks.recovery_backups.secret', 'test-secret');
        config()->set('filesystems.disks.recovery_backups.bucket', 'private-test');
        config()->set('backup.backup.source.databases', ['sqlite']);

        $this->artisan('recovery:restore-db', ['filename' => 'backup.zip'])->assertFailed();
    }

    public function test_backup_package_creates_an_aes_256_zip_for_selected_files(): void
    {
        Storage::fake('recovery_backups');
        $fixture = tempnam(sys_get_temp_dir(), 'recovery-source-');
        file_put_contents($fixture, 'fixture contents');

        config()->set('backup.backup.password', str_repeat('a', 32));
        config()->set('backup.backup.source.files.include', [$fixture]);
        config()->set('backup.backup.source.files.relative_path', dirname($fixture));
        config()->set('backup.backup.source.databases', []);
        SpatieBackupConfig::rebind();

        try {
            $this->assertSame(0, Artisan::call('backup:run', [
                '--only-files' => true,
                '--disable-notifications' => true,
            ]), Artisan::output());

            $path = Storage::disk('recovery_backups')->files('probablue-recovery')[0];
            $zip = new ZipArchive;
            $this->assertTrue($zip->open(Storage::disk('recovery_backups')->path($path), ZipArchive::RDONLY));
            try {
                $stat = $zip->statName(basename($fixture));
                $this->assertSame(ZipArchive::EM_AES_256, $stat['encryption_method']);
            } finally {
                $zip->close();
            }
        } finally {
            unlink($fixture);
        }
    }

    private function zip(bool $encrypted): string
    {
        $path = tempnam(sys_get_temp_dir(), 'recovery-test-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('.env', 'APP_KEY=base64:test');
        $zip->addFromString('db-dumps/test.sql', 'CREATE TABLE example (id INT);');

        if ($encrypted) {
            $zip->setPassword(str_repeat('a', 32));
            $zip->setEncryptionName('.env', ZipArchive::EM_AES_256);
            $zip->setEncryptionName('db-dumps/test.sql', ZipArchive::EM_AES_256);
        }

        $zip->close();

        return $path;
    }
}
