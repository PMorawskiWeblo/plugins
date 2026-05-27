<?php
/**
 * Custom Logger for Loyalty Program
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loyalty Program Logger Class
 */
class Loyalty_Program_Logger
{

    /**
     * Maximum log file size in bytes (5 MB)
     * 
     * @var int
     */
    const MAX_LOG_SIZE = 5242880; // 5 * 1024 * 1024

    /**
     * Log file path
     * 
     * @var string
     */
    private static $log_file = null;

    /**
     * Initialize logger
     * 
     * @return void
     */
    public static function init()
    {
        if (self::$log_file === null) {
            // Use plugin directory for logs
            $log_dir = LOYALTY_PROGRAM_PLUGIN_DIR . 'logs';
            
            // Create logs directory if it doesn't exist
            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);
                
                // Create .htaccess to protect log files
                $htaccess_file = $log_dir . '/.htaccess';
                if (!file_exists($htaccess_file)) {
                    file_put_contents($htaccess_file, "Deny from all\n");
                }
                
                // Create index.php to prevent directory listing
                $index_file = $log_dir . '/index.php';
                if (!file_exists($index_file)) {
                    file_put_contents($index_file, "<?php\n// Silence is golden.\n");
                }
            }
            
            self::$log_file = $log_dir . '/debug.log';
        }
    }

    /**
     * Check if debug logging is enabled
     * 
     * @return bool
     */
    public static function is_enabled()
    {
        return get_option('loyalty_program_debug_enabled', 'no') === 'yes';
    }

    /**
     * Log a message
     * 
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error, debug)
     * @param array $context Additional context data
     * @return bool
     */
    public static function log($message, $level = 'info', $context = array())
    {
        // Check if logging is enabled
        if (!self::is_enabled()) {
            return false;
        }

        self::init();

        // Check file size and rotate if necessary
        if (file_exists(self::$log_file) && filesize(self::$log_file) >= self::MAX_LOG_SIZE) {
            self::rotate_log();
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        
        // Format context if provided
        $context_str = '';
        if (!empty($context)) {
            $context_str = ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $log_entry = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $level_upper,
            $message,
            $context_str
        );

        // Write to log file
        return error_log($log_entry, 3, self::$log_file);
    }

    /**
     * Log info message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function info($message, $context = array())
    {
        return self::log($message, 'info', $context);
    }

    /**
     * Log warning message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function warning($message, $context = array())
    {
        return self::log($message, 'warning', $context);
    }

    /**
     * Log error message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function error($message, $context = array())
    {
        return self::log($message, 'error', $context);
    }

    /**
     * Log debug message
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public static function debug($message, $context = array())
    {
        return self::log($message, 'debug', $context);
    }

    /**
     * Rotate log file
     * 
     * @return void
     */
    private static function rotate_log()
    {
        if (!file_exists(self::$log_file)) {
            return;
        }

        $backup_file = self::$log_file . '.old';
        
        // Remove old backup if exists
        if (file_exists($backup_file)) {
            unlink($backup_file);
        }

        // Rename current log to backup
        rename(self::$log_file, $backup_file);
    }

    /**
     * Clear log file
     * 
     * @return bool
     */
    public static function clear_log()
    {
        self::init();

        if (file_exists(self::$log_file)) {
            return unlink(self::$log_file);
        }

        return true;
    }

    /**
     * Get log file path
     * 
     * @return string
     */
    public static function get_log_file_path()
    {
        self::init();
        return self::$log_file;
    }

    /**
     * Get log file size
     * 
     * @return int|false
     */
    public static function get_log_size()
    {
        self::init();

        if (file_exists(self::$log_file)) {
            return filesize(self::$log_file);
        }

        return 0;
    }

    /**
     * Get formatted log file size
     * 
     * @return string
     */
    public static function get_formatted_log_size()
    {
        $size = self::get_log_size();

        if ($size === 0) {
            return '0 B';
        }

        $units = array('B', 'KB', 'MB', 'GB');
        $factor = floor((strlen($size) - 1) / 3);

        return sprintf("%.2f %s", $size / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Get log content (last N lines)
     * 
     * @param int $lines Number of lines to retrieve
     * @return string
     */
    public static function get_log_content($lines = 100)
    {
        self::init();

        if (!file_exists(self::$log_file)) {
            return __('Log file is empty or does not exist.', 'loyalty-program');
        }

        $file = new SplFileObject(self::$log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        $start_line = max(0, $total_lines - $lines);
        
        $file->seek($start_line);
        $content = '';

        while (!$file->eof()) {
            $content .= $file->fgets();
        }

        return $content;
    }

    /**
     * Download log file
     * 
     * @return void
     */
    public static function download_log()
    {
        self::init();

        if (!file_exists(self::$log_file)) {
            wp_die(__('Log file does not exist.', 'loyalty-program'));
        }

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="loyalty-program-debug-' . date('Y-m-d-H-i-s') . '.log"');
        header('Content-Length: ' . filesize(self::$log_file));
        
        readfile(self::$log_file);
        exit;
    }
}

