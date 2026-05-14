<?php

if (!defined('ABSPATH')) {
    exit;
}

class Weblo_CC_Logger
{
    private string $log_file;
    private int $max_size_bytes;

    public function __construct(string $log_file, int $max_size_bytes = 5242880)
    {
        $this->log_file       = $log_file;
        $this->max_size_bytes = $max_size_bytes;
    }

    public function log(string $message): void
    {
        $dir = dirname($this->log_file);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        if (file_exists($this->log_file) && filesize($this->log_file) >= $this->max_size_bytes) {
            file_put_contents($this->log_file, '');
        }

        $timestamp = current_time('mysql');
        $line      = sprintf('[%s] %s' . PHP_EOL, $timestamp, $message);
        file_put_contents($this->log_file, $line, FILE_APPEND | LOCK_EX);
    }
}