<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * استعادة نسخة احتياطية أنشأها backup:run.
 *   php artisan backup:restore latest --force                 (آخر نسخة على BACKUP_DISK)
 *   php artisan backup:restore backups/diwan-....dbk.enc      (ملف على BACKUP_DISK)
 *   php artisan backup:restore C:\path\diwan-....dbk.enc      (ملف على الجهاز)
 *   php artisan backup:restore https://.../diwan-....dbk.enc  (رابط تحميل مباشر)
 * قبل الاستعادة تُحفظ نسخة من البيانات الحالية تلقائيًا (backups/pre-restore-...).
 */
class BackupRestore extends Command
{
    protected $signature = 'backup:restore {source : latest | path on BACKUP_DISK | local file | https URL}
                            {--password= : Backup password (defaults to BACKUP_PASSWORD)}
                            {--force : Do not ask for confirmation}';

    protected $description = 'Restore the database from a backup created by backup:run (replaces current data)';

    public function handle(DatabaseBackup $backup): int
    {
        $password = $this->option('password') ?: (config('store.backup.password') ?: null);
        $disk = Storage::disk((string) config('store.backup.disk'));

        try {
            $file = $this->resolveSource((string) $this->argument('source'), $disk);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('This REPLACES all current store data with the backup. Continue?')) {
            @unlink($file);
            return self::FAILURE;
        }

        try {
            // شبكة أمان: نسخة من الوضع الحالي قبل الاستبدال
            $safety = tempnam(sys_get_temp_dir(), 'dbk');
            $backup->create($safety, $password);
            $stream = fopen($safety, 'rb');
            $disk->writeStream('backups/pre-restore-'.now()->format('Y-m-d-His').($password ? '.dbk.enc' : '.dbk.gz'), $stream);
            if (is_resource($stream)) fclose($stream);
            @unlink($safety);

            $stats = $backup->restore($file, $password);
            $this->info("✔ Restored {$stats['rows']} rows in {$stats['tables']} tables (backup from {$stats['created_at']}).");
            $this->line('Admin sessions were not restored: log in to /admin again.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Restore failed, database unchanged: '.$e->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($file);
        }
    }

    /** Copies the backup to a local temp file and returns its path. */
    private function resolveSource(string $source, $disk): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dbk');

        if (str_starts_with($source, 'https://')) {
            Http::timeout(300)->sink($tmp)->get($source)->throw();
            return $tmp;
        }

        if ($source === 'latest') {
            $source = collect($disk->files('backups'))
                ->filter(fn ($f) => str_starts_with(basename($f), 'diwan-'))->sort()->last()
                ?? throw new \RuntimeException('No backups found on BACKUP_DISK.');
            $this->line("Using {$source}");
        }

        if (is_file($source)) {
            copy($source, $tmp);
            return $tmp;
        }

        if ($disk->exists($source)) {
            file_put_contents($tmp, $disk->readStream($source));
            return $tmp;
        }

        @unlink($tmp);
        throw new \RuntimeException("Backup [{$source}] not found.");
    }
}
