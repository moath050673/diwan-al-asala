<?php

namespace App\Services;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Portable database backup (works on MySQL, MariaDB, SQLite, PostgreSQL — no mysqldump needed).
 *
 * File format: gzip of JSON lines — a header line listing the tables, then one line per row.
 * Binary values (product images in stored_files) are base64-encoded. When a password is
 * given, the gzip is encrypted in 1 MB chunks (AES-256-CBC + HMAC via Laravel's Encrypter),
 * so it can be decrypted on any server with the same password (independent of APP_KEY).
 *
 * Restore replaces the data of every table in the backup inside one transaction:
 * if anything fails, the database is left unchanged.
 */
class DatabaseBackup
{
    public const FORMAT = 'diwan-backup';
    private const ENCRYPTED_MAGIC = "DIWANBK1-ENC\n";
    private const CHUNK = 1048576;

    /** Tables that are rebuilt automatically or must not be restored (sessions, login tokens, caches). */
    private const EXCLUDED = [
        'migrations', 'cache', 'cache_locks', 'sessions', 'personal_access_tokens',
        'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens',
    ];

    /**
     * Writes the backup to $target. Returns ['tables' => n, 'rows' => n, 'bytes' => n].
     */
    public function create(string $target, ?string $password = null): array
    {
        $tables = $this->tables();
        $gzPath = $password ? $target.'.gz.tmp' : $target;
        $gz = gzopen($gzPath, 'wb6');
        if (!$gz) {
            throw new RuntimeException("Cannot write backup file [{$gzPath}].");
        }

        $rows = 0;
        try {
            $this->writeLine($gz, [
                'format' => self::FORMAT,
                'version' => 1,
                'created_at' => now()->toIso8601String(),
                'app' => config('app.name'),
                'driver' => DB::connection()->getDriverName(),
                'tables' => $tables,
            ]);

            foreach ($tables as $table) {
                $columns = Schema::getColumnListing($table);
                $orderBy = in_array('id', $columns, true) ? 'id' : $columns[0];
                // صفوف الملفات كبيرة (صور) — نقرأها بأعداد صغيرة لتوفير الذاكرة
                $chunk = $table === 'stored_files' ? 5 : 500;

                foreach (DB::table($table)->orderBy($orderBy)->lazy($chunk) as $row) {
                    $this->writeLine($gz, ['t' => $table, 'r' => array_map($this->encodeValue(...), (array) $row)]);
                    $rows++;
                }
            }
        } finally {
            gzclose($gz);
        }

        if ($password) {
            $this->encryptFile($gzPath, $target, $password);
            @unlink($gzPath);
        }

        return ['tables' => count($tables), 'rows' => $rows, 'bytes' => filesize($target)];
    }

    /**
     * Replaces the current data with the backup's data. Returns ['tables' => n, 'rows' => n, 'created_at' => ...].
     */
    public function restore(string $source, ?string $password = null): array
    {
        $gzPath = $source;
        $decrypted = null;

        if ($this->isEncrypted($source)) {
            if (!$password) {
                throw new RuntimeException('This backup is encrypted: BACKUP_PASSWORD (or --password) is required.');
            }
            $gzPath = $decrypted = tempnam(sys_get_temp_dir(), 'dbk');
            $this->decryptFile($source, $decrypted, $password);
        }

        try {
            return $this->restoreGzip($gzPath);
        } finally {
            if ($decrypted) @unlink($decrypted);
        }
    }

    public function isEncrypted(string $path): bool
    {
        $fh = fopen($path, 'rb');
        $head = fread($fh, strlen(self::ENCRYPTED_MAGIC));
        fclose($fh);

        return $head === self::ENCRYPTED_MAGIC;
    }

    /** @return list<string> */
    private function tables(): array
    {
        $tables = Schema::getTableListing(Schema::getCurrentSchemaListing(), false);

        return array_values(array_filter($tables, fn ($t) => !in_array($t, self::EXCLUDED, true) && !str_starts_with($t, 'sqlite_')));
    }

    private function restoreGzip(string $path): array
    {
        $gz = gzopen($path, 'rb');
        if (!$gz) {
            throw new RuntimeException('Cannot open backup file.');
        }

        try {
            $header = json_decode((string) gzgets($gz), true);
            if (($header['format'] ?? null) !== self::FORMAT) {
                throw new RuntimeException('Not a valid backup file (wrong password or corrupted file).');
            }

            $existing = $this->tables();
            $tables = array_values(array_intersect($header['tables'], $existing));
            $columns = [];
            foreach ($tables as $table) {
                $columns[$table] = array_flip(Schema::getColumnListing($table));
            }

            $rows = 0;
            Schema::withoutForeignKeyConstraints(function () use ($gz, $tables, $columns, &$rows) {
                DB::transaction(function () use ($gz, $tables, $columns, &$rows) {
                    // SQLite يتجاهل إيقاف المفاتيح الأجنبية داخل transaction — نؤجل فحصها لنهاية العملية
                    if (DB::connection()->getDriverName() === 'sqlite') {
                        DB::statement('PRAGMA defer_foreign_keys = ON');
                    }

                    foreach ($tables as $table) {
                        DB::table($table)->delete();
                    }

                    $buffer = [];
                    $bufferTable = null;
                    $bufferBytes = 0;
                    $flush = function () use (&$buffer, &$bufferTable, &$bufferBytes) {
                        if ($buffer) DB::table($bufferTable)->insert($buffer);
                        $buffer = [];
                        $bufferBytes = 0;
                    };

                    while (($line = gzgets($gz)) !== false) {
                        if (trim($line) === '') continue;
                        $item = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                        if (!isset($columns[$item['t']])) continue; // table no longer exists

                        if ($item['t'] !== $bufferTable || count($buffer) >= 200 || $bufferBytes > 4 * 1024 * 1024) {
                            $flush();
                            $bufferTable = $item['t'];
                        }

                        // only columns that still exist in the current schema
                        $row = array_intersect_key(array_map($this->decodeValue(...), $item['r']), $columns[$item['t']]);
                        $buffer[] = $row;
                        $bufferBytes += strlen($line);
                        $rows++;
                    }
                    $flush();
                });
            });
        } finally {
            gzclose($gz);
        }

        Cache::forget('settings.all');

        return ['tables' => count($tables), 'rows' => $rows, 'created_at' => $header['created_at'] ?? null];
    }

    private function writeLine($gz, array $data): void
    {
        gzwrite($gz, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    private function encodeValue(mixed $value): mixed
    {
        if (is_resource($value)) {
            $value = stream_get_contents($value); // pgsql bytea
        }
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            return ['__b64' => base64_encode($value)];
        }

        return $value;
    }

    private function decodeValue(mixed $value): mixed
    {
        return is_array($value) && isset($value['__b64']) ? base64_decode($value['__b64']) : $value;
    }

    private function encrypter(string $password): Encrypter
    {
        return new Encrypter(hash('sha256', $password, true), 'aes-256-cbc');
    }

    private function encryptFile(string $from, string $to, string $password): void
    {
        $encrypter = $this->encrypter($password);
        $in = fopen($from, 'rb');
        $out = fopen($to, 'wb');
        fwrite($out, self::ENCRYPTED_MAGIC);
        while (!feof($in)) {
            $chunk = fread($in, self::CHUNK);
            if ($chunk === '' || $chunk === false) break;
            fwrite($out, $encrypter->encryptString($chunk)."\n");
        }
        fclose($in);
        fclose($out);
    }

    private function decryptFile(string $from, string $to, string $password): void
    {
        $encrypter = $this->encrypter($password);
        $in = fopen($from, 'rb');
        $out = fopen($to, 'wb');
        fread($in, strlen(self::ENCRYPTED_MAGIC));

        try {
            while (($line = fgets($in)) !== false) {
                if (trim($line) === '') continue;
                fwrite($out, $encrypter->decryptString(trim($line)));
            }
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            throw new RuntimeException('Wrong backup password (or corrupted file).');
        } finally {
            fclose($in);
            fclose($out);
        }
    }
}
