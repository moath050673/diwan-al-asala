<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackup;
use App\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * نسخة احتياطية كاملة لقاعدة البيانات (بما فيها صور المنتجات والإيصالات المخزنة فيها).
 * تُشغَّل يوميًا عبر الجدولة (routes/console.php) أو يدويًا: php artisan backup:run
 */
class BackupRun extends Command
{
    protected $signature = 'backup:run {--no-telegram : لا ترسل النسخة إلى Telegram}';

    protected $description = 'Create a full database backup (saved to BACKUP_DISK, optionally sent to Telegram)';

    private const TELEGRAM_LIMIT = 49 * 1024 * 1024;

    public function handle(DatabaseBackup $backup, TelegramClient $telegram): int
    {
        $diskName = (string) config('store.backup.disk');
        $password = config('store.backup.password') ?: null;
        $toTelegram = config('store.backup.telegram') && !$this->option('no-telegram');

        // النسخة لا تُحفظ داخل نفس قاعدة البيانات (لا فائدة منها إن ضاعت القاعدة، وتتضاعف في كل نسخة)
        if (config("filesystems.disks.{$diskName}.driver") === 'database') {
            $this->error("BACKUP_DISK [{$diskName}] stores files in the database itself — choose another disk.");
            return self::FAILURE;
        }
        if ($toTelegram && !$password) {
            $this->error('BACKUP_PASSWORD is required to send backups to Telegram (the file contains customer data).');
            return self::FAILURE;
        }

        $name = 'diwan-'.now()->format('Y-m-d-His').($password ? '.dbk.enc' : '.dbk.gz');
        $tmp = tempnam(sys_get_temp_dir(), 'dbk');

        try {
            $stats = $backup->create($tmp, $password);

            $disk = Storage::disk($diskName);
            $stream = fopen($tmp, 'rb');
            $disk->writeStream('backups/'.$name, $stream);
            if (is_resource($stream)) fclose($stream);
            $this->prune($disk);

            $size = number_format($stats['bytes'] / 1048576, 2).' MB';
            $this->info("✔ Backup {$name}: {$stats['tables']} tables, {$stats['rows']} rows, {$size} → disk [{$diskName}]");

            if ($toTelegram) {
                if ($stats['bytes'] > self::TELEGRAM_LIMIT) {
                    $this->warn('Backup is larger than Telegram\'s 50 MB limit — not sent.');
                    Log::warning('Backup too large for Telegram', ['bytes' => $stats['bytes']]);
                } elseif ($telegram->sendDocument($tmp, $name, "🗄 نسخة احتياطية — {$stats['rows']} سجل، {$size}")) {
                    $this->info('✔ Sent to Telegram.');
                } else {
                    $this->warn('Could not send the backup to Telegram (see logs).');
                }
            }

            Log::info('Database backup created', ['file' => $name, ...$stats]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup failed: '.$e->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($tmp);
        }
    }

    private function prune($disk): void
    {
        $keep = max(1, (int) config('store.backup.keep'));
        $files = collect($disk->files('backups'))
            ->filter(fn ($f) => str_starts_with(basename($f), 'diwan-'))
            ->sort()->values();

        $files->slice(0, max(0, $files->count() - $keep))->each(fn ($f) => $disk->delete($f));
    }
}
