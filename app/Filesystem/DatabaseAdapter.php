<?php

namespace App\Filesystem;

use Illuminate\Database\ConnectionInterface;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

/**
 * Flysystem adapter that keeps files in the `stored_files` table.
 * Registered as the "database" driver so it works through Laravel's Storage facade
 * exactly like local/S3 disks — switching storage later is a config change only.
 */
class DatabaseAdapter implements FilesystemAdapter
{
    private FinfoMimeTypeDetector $mimes;

    public function __construct(
        private ConnectionInterface $db,
        private string $bucket,
        private ?string $baseUrl = null,
        private string $table = 'stored_files',
    ) {
        $this->mimes = new FinfoMimeTypeDetector();
    }

    /** يستدعيها Storage::url() — متاح فقط للأقراص العامة التي لها 'url' في الإعدادات */
    public function getUrl(string $path): string
    {
        if ($this->baseUrl === null) {
            throw new \RuntimeException("Disk bucket [{$this->bucket}] is private and has no public URL.");
        }

        return rtrim($this->baseUrl, '/').'/'.$this->normalize($path);
    }

    private function query()
    {
        return $this->db->table($this->table)->where('bucket', $this->bucket);
    }

    private function row(string $path, array $columns = ['*']): ?object
    {
        return $this->query()->where('path', $this->normalize($path))->first($columns);
    }

    private function normalize(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    public function fileExists(string $path): bool
    {
        return $this->query()->where('path', $this->normalize($path))->exists();
    }

    public function directoryExists(string $path): bool
    {
        return $this->query()->where('path', 'like', $this->normalize($path).'/%')->exists();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $path = $this->normalize($path);
        $now = now();

        $this->query()->updateOrInsert(['bucket' => $this->bucket, 'path' => $path], [
            'contents' => $contents,
            'mime_type' => $this->mimes->detectMimeType($path, $contents) ?? 'application/octet-stream',
            'size' => strlen($contents),
            'visibility' => $config->get('visibility', 'private'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->write($path, stream_get_contents($contents), $config);
    }

    public function read(string $path): string
    {
        $row = $this->row($path, ['contents']);
        if (!$row) {
            throw UnableToReadFile::fromLocation($path, 'File not found');
        }

        // pgsql يعيد bytea كـ stream
        return is_resource($row->contents) ? stream_get_contents($row->contents) : (string) $row->contents;
    }

    public function readStream(string $path)
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $this->read($path));
        rewind($stream);

        return $stream;
    }

    public function delete(string $path): void
    {
        $this->query()->where('path', $this->normalize($path))->delete();
    }

    public function deleteDirectory(string $path): void
    {
        $this->query()->where('path', 'like', $this->normalize($path).'/%')->delete();
    }

    public function createDirectory(string $path, Config $config): void
    {
        // المجلدات افتراضية — تُستنتج من مسارات الملفات
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->query()->where('path', $this->normalize($path))->update(['visibility' => $visibility]);
    }

    private function attributes(string $path): FileAttributes
    {
        $row = $this->row($path, ['path', 'size', 'mime_type', 'visibility', 'updated_at']);
        if (!$row) {
            throw UnableToRetrieveMetadata::create($path, 'metadata', 'File not found');
        }

        return new FileAttributes($row->path, (int) $row->size, $row->visibility,
            $row->updated_at ? strtotime($row->updated_at) : null, $row->mime_type);
    }

    public function visibility(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = $this->normalize($path);
        $rows = $this->query()
            ->when($prefix !== '', fn ($q) => $q->where('path', 'like', $prefix.'/%'))
            ->orderBy('path')
            ->get(['path', 'size', 'mime_type', 'visibility', 'updated_at']);

        $dirs = [];
        foreach ($rows as $row) {
            $relative = $prefix === '' ? $row->path : substr($row->path, strlen($prefix) + 1);
            if (!$deep && str_contains($relative, '/')) {
                $dir = ($prefix === '' ? '' : $prefix.'/').strstr($relative, '/', true);
                if (!isset($dirs[$dir])) {
                    $dirs[$dir] = true;
                    yield new DirectoryAttributes($dir);
                }
                continue;
            }
            yield new FileAttributes($row->path, (int) $row->size, $row->visibility,
                $row->updated_at ? strtotime($row->updated_at) : null, $row->mime_type);
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->delete($destination);
        $this->query()->where('path', $this->normalize($source))
            ->update(['path' => $this->normalize($destination), 'updated_at' => now()]);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->write($destination, $this->read($source), $config);
    }
}
