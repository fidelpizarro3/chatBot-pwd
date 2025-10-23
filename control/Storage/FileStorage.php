<?php
namespace Control\Storage;

class FileStorage
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }
    }

    private function path(string $key): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . md5($key) . '.json';
    }

    public function get(string $key)
    {
        $p = $this->path($key);
        if (!is_file($p)) return null;
        $data = @file_get_contents($p);
        return $data === false ? null : json_decode($data, true);
    }

    public function save(string $key, $value): void
    {
        $p = $this->path($key);
        @file_put_contents($p, json_encode($value));
    }

    public function delete(string $key): void
    {
        $p = $this->path($key);
        if (is_file($p)) @unlink($p);
    }
}
