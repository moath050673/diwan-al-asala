<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ImageOptimizer;
use App\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * فحص صحة الموقع على الاستضافة: php artisan app:health
 * لا يعرض أي كلمة مرور أو توكن — فقط هل الإعداد صحيح أم لا.
 */
class HealthCheck extends Command
{
    protected $signature = 'app:health';

    protected $description = 'Check database, storage, cache, queue, sessions, mail, HTTPS, environment and permissions';

    private array $rows = [];
    private int $failures = 0;

    public function handle(ImageOptimizer $images, TelegramClient $telegram): int
    {
        $this->rows = [];
        $this->failures = 0;
        $production = app()->isProduction();

        // ---------- Environment ----------
        $this->check('APP_ENV', $production ? 'ok' : 'warn', app()->environment());
        $this->check('APP_DEBUG off', config('app.debug') ? ($production ? 'fail' : 'warn') : 'ok', config('app.debug') ? 'true — shows code & secrets on errors!' : 'false');
        $this->check('APP_KEY set', config('app.key') ? 'ok' : 'fail');
        $https = str_starts_with((string) config('app.url'), 'https://');
        $this->check('APP_URL uses HTTPS', $https ? 'ok' : ($production ? 'fail' : 'warn'), (string) config('app.url'));
        $this->check('Secure session cookie', config('session.secure') ? 'ok' : ($production ? 'fail' : 'warn'), 'SESSION_SECURE_COOKIE='.var_export((bool) config('session.secure'), true));
        $this->check('Config cached', app()->configurationIsCached() ? 'ok' : ($production ? 'warn' : 'ok'), app()->configurationIsCached() ? 'yes' : 'no (php artisan optimize)');
        $this->check('Routes cached', app()->routesAreCached() ? 'ok' : ($production ? 'warn' : 'ok'), app()->routesAreCached() ? 'yes' : 'no');

        // ---------- Database ----------
        try {
            DB::connection()->getPdo();
            $this->check('Database connection', 'ok', DB::connection()->getDriverName().' / '.DB::connection()->getDatabaseName());

            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));
            $pending = array_diff($files, $migrator->getRepository()->getRan());
            $this->check('Migrations up to date', $pending ? 'fail' : 'ok', $pending ? count($pending).' pending: php artisan migrate --force' : count($files).' ran');

            $admin = User::where('role', 'admin')->where('status', 'active')->first();
            $this->check('Active admin account', $admin ? 'ok' : 'fail', $admin ? 'yes' : 'run AdminUserSeeder');
            if ($admin) {
                $this->check('Admin changed initial password', $admin->must_change_password ? 'warn' : 'ok', $admin->must_change_password ? 'change it in /admin/settings' : 'yes');
            }
            $this->check('Store settings seeded', DB::table('settings')->exists() ? 'ok' : 'warn', DB::table('settings')->exists() ? 'yes' : 'run SettingSeeder');
        } catch (\Throwable $e) {
            $this->check('Database connection', 'fail', Str::limit($e->getMessage(), 80));
        }

        // ---------- Storage ----------
        foreach (['images' => 'Product images disk', 'receipts' => 'Receipts disk (private)'] as $key => $label) {
            $diskName = (string) config("store.disks.{$key}");
            try {
                $disk = Storage::disk($diskName);
                $probe = 'health/'.Str::random(12).'.txt';
                $disk->put($probe, 'ok');
                $ok = $disk->get($probe) === 'ok';
                $disk->delete($probe);
                $this->check($label, $ok ? 'ok' : 'fail', $diskName.' (write/read/delete)');
            } catch (\Throwable $e) {
                $this->check($label, 'fail', $diskName.': '.Str::limit($e->getMessage(), 60));
            }
        }
        $ephemeral = $production && in_array(config('store.disks.images'), ['public', 'local'], true);
        $this->check('Uploads survive redeploys', $ephemeral ? 'warn' : 'ok', $ephemeral ? 'local disk on cloud hosting is wiped on deploy — use db_public/db_private' : 'yes');
        if (config('store.disks.images') === 'public') {
            $this->check('storage:link', file_exists(public_path('storage')) ? 'ok' : 'fail', 'public/storage');
        }
        $this->check('Image optimization (GD)', $images->available() ? 'ok' : 'warn', $images->available() ? ($images->supportsWebp() ? 'GD + WebP' : 'GD (no WebP)') : 'GD missing — originals are stored unoptimized');

        // ---------- Cache / Session / Queue ----------
        try {
            $key = 'health:'.Str::random(8);
            Cache::put($key, 'ok', 10);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);
            $this->check('Cache', $ok ? 'ok' : 'fail', config('cache.default'));
        } catch (\Throwable $e) {
            $this->check('Cache', 'fail', config('cache.default').': '.Str::limit($e->getMessage(), 60));
        }
        $this->check('Sessions', 'ok', config('session.driver').' (admin panel uses API tokens)');
        $queue = config('queue.default');
        $this->check('Queue', 'ok', $queue.($queue === 'sync' ? ' (jobs run immediately)' : ''));
        $this->check('Failed jobs table', $this->tableExists('failed_jobs') ? 'ok' : 'warn');

        // ---------- Notifications / Mail ----------
        $this->check('Telegram order notifications', $telegram->configured() ? 'ok' : 'warn', $telegram->configured() ? 'configured' : 'TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID missing');
        $mailer = config('mail.default');
        $ownerEmail = config('store.owner_email');
        $mailOk = !$ownerEmail || !in_array($mailer, ['log', 'array'], true);
        $this->check('Mail', $mailOk ? 'ok' : 'warn', $mailer.($mailOk ? '' : ' — order emails are only written to the log'));

        // ---------- Backups ----------
        $backupDisk = (string) config('store.backup.disk');
        $offsite = config('store.backup.telegram') && config('store.backup.password');
        $this->check('Off-site backups', $offsite ? 'ok' : 'warn', $offsite ? 'daily → Telegram (encrypted)' : 'set BACKUP_TELEGRAM=true + BACKUP_PASSWORD (or use Laravel Cloud DB backups)');
        $this->check('Backup disk', config("filesystems.disks.{$backupDisk}") ? 'ok' : 'fail', $backupDisk);

        // ---------- PHP / Permissions ----------
        $opcache = function_exists('opcache_get_status') && (@opcache_get_status(false)['opcache_enabled'] ?? false);
        $this->check('OPcache', $opcache ? 'ok' : ($production ? 'warn' : 'ok'), $opcache ? 'enabled' : 'disabled');
        foreach ([storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $dir) {
            $this->check('Writable '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $dir), is_writable($dir) ? 'ok' : 'fail');
        }
        $this->check('.env not inside public/', !file_exists(public_path('.env')) ? 'ok' : 'fail');

        $this->table(['', 'Check', 'Details'], $this->rows);
        $this->newLine();
        $this->failures
            ? $this->error("{$this->failures} problem(s) must be fixed.")
            : $this->info('✔ No blocking problems.');

        return $this->failures ? self::FAILURE : self::SUCCESS;
    }

    private function check(string $label, string $status, string $details = ''): void
    {
        if ($status === 'fail') $this->failures++;
        $this->rows[] = [['ok' => '✔', 'warn' => '⚠', 'fail' => '✘'][$status], $label, $details];
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
