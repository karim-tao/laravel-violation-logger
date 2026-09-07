<?php

namespace KarimTao\LaravelViolationLogger;

use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Model;

final class ViolationLogger
{
    private array $packages = [];

    public function __construct(private string $path) {}

    public function log(string $type, Model $model, array|string $detail): void
    {
        $path = $this->path;
        $class = get_class($model);
        $callSite = $this->findCallSite();
        $details = (array) $detail;

        if (! is_writable(dirname($path))) {
            throw new ViolationLoggerException("Cannot open violations file: {$path}");
        }

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new ViolationLoggerException("Cannot open violations file: {$path}");
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new ViolationLoggerException("Cannot acquire exclusive lock on: {$path}");
            }

            $contents = stream_get_contents($handle);

            if ($contents === false) {
                throw new ViolationLoggerException("Cannot read contents of: {$path}");
            }

            $data = $contents ? (json_decode($contents, true) ?? []) : [];

            $existing = $data[$callSite][$class][$type] ?? [];

            $data[$callSite][$class][$type] = collect($existing)
                ->merge($details)
                ->unique()
                ->values()
                ->all();

            if (! ftruncate($handle, 0) || rewind($handle) === false) {
                throw new ViolationLoggerException("Cannot reset file pointer: {$path}");
            }

            if (fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new ViolationLoggerException("Cannot write to: {$path}");
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function findCallSite(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $base = (string) realpath((string) InstalledVersions::getRootPackage()['install_path']);

        foreach ($trace as $frame) {
            if (! isset($frame['file'])) {
                continue;
            }

            $file = $frame['file'];

            if (str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) || $this->belongsToPackage($file)) {
                continue;
            }

            if (str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
                $file = substr($file, strlen($base) + 1);
            }

            return $file . ':' . $frame['line'];
        }

        throw new ViolationLoggerException('No application frame found for the violation');
    }

    private function belongsToPackage(string $file): bool
    {
        if ($this->packages === []) {
            foreach (InstalledVersions::getAllRawData() as $installed) {
                foreach ($installed['versions'] as $name => $package) {
                    if ($name === $installed['root']['name']) {
                        continue;
                    }

                    if (isset($package['install_path']) && ($path = realpath($package['install_path'])) !== false) {
                        $this->packages[] = $path . DIRECTORY_SEPARATOR;
                    }
                }
            }
        }

        foreach ($this->packages as $package) {
            if (str_starts_with($file, $package)) {
                return true;
            }
        }

        return false;
    }
}
