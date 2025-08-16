<?php
/**
 * MasterHotelLogHelper
 * Simple static helper for logging plugin events
 */
class MasterHotelLogHelper {
    const LOG_FILE = 'masterhotel.log';

    /**
     * Write a message to the log file
     * @param string $message
     */
    public static function write($message) {
        $log_path = self::get_log_path();
        $date = date('Y-m-d H:i:s');
        $entry = "[$date] $message\n";
        file_put_contents($log_path, $entry, FILE_APPEND);
    }

    /**
     * Get the log file path
     * @return string
     */
    public static function get_log_path() {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/masterhotel-logs';
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir . '/' . self::LOG_FILE;
    }

    /**
     * Get the log contents
     * @return string
     */
    public static function get_log() {
        $log_path = self::get_log_path();
        if (file_exists($log_path)) {
            return file_get_contents($log_path);
        }
        return '';
    }

    /**
     * Clear the log file
     */
    public static function clear_log() {
        $log_path = self::get_log_path();
        if (file_exists($log_path)) {
            file_put_contents($log_path, '');
        }
    }
}
