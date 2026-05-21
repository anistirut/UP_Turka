<?php

class CacheContext
{
    private string $dir;

    public function __construct(?string $cacheDir = null)
    {
        $this->dir = rtrim(
            $cacheDir ?? __DIR__ . '/../../storage/cache',
            '/\\'
        ) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        $htaccess = dirname($this->dir) . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
        }
    }

    public function get(string $key): mixed
    {
        $file = $this->path($key);
        if (!file_exists($file)) {
            return null;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return null;
        }

        $data = @unserialize($raw);
        if (!is_array($data)) {
            return null;
        }

        if ($data['expires_at'] !== null && time() > $data['expires_at']) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 60): void
    {
        file_put_contents(
            $this->path($key),
            serialize([
                'value'      => $value,
                'expires_at' => $ttl > 0 ? time() + $ttl : null,
            ]),
            LOCK_EX
        );
    }

    public function delete(string $key): void
    {
        $file = $this->path($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function deleteByPrefix(string $prefix): void
    {
        $pattern = $this->dir . $this->sanitize($prefix) . '*.cache';
        foreach (glob($pattern) ?: [] as $file) {
            @unlink($file);
        }
    }

    private function path(string $key): string
    {
        return $this->dir . $this->sanitize($key) . '.cache';
    }

    private function sanitize(string $key): string
    {
        return preg_replace('/[^a-z0-9_\-]/i', '_', $key);
    }
}
