<?php
/**
 * MasterHotelCurlHelper
 * Simple static helper for sending POST requests with cURL
 */
class MasterHotelCurlHelper {
    /**
     * Send a POST request with JSON data
     * @param string $url
     * @param array $data
     * @param array $headers (optional)
     * @return array [ 'response' => string, 'http_code' => int, 'error' => string|null ]
     */
    public static function post_json($url, $data, $headers = array()) {
        // Logging
        if (!class_exists('MasterHotelLogHelper')) {
            $log_path = dirname(__FILE__) . '/MasterHotelLogHelper.php';
            if (file_exists($log_path)) {
                require_once $log_path;
            }
        }
        // Add X-API-Secret header if available from config
        $api_secret = '';
        if (!class_exists('MasterHotelConfig')) {
            $config_path = dirname(__FILE__,2) . '/config.php';
            if (file_exists($config_path)) {
                require_once $config_path;
            }
        }
        if (class_exists('MasterHotelConfig')) {
            $api_secret = MasterHotelConfig::get_config('api_secret', '');
        }
        $default_headers = array('Content-Type: application/json');
        if (!empty($api_secret)) {
            $default_headers[] = 'X-API-Secret: ' . $api_secret;
        }
        if (!empty($headers)) {
            $default_headers = array_merge($default_headers, $headers);
        }
        $log_entry = "POST $url\nHeaders: " . json_encode($default_headers) . "\nData: " . json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $default_headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $log_entry .= "\nResponse: $response\nHTTP Code: $http_code\nError: $error\n";
        if (class_exists('MasterHotelLogHelper')) {
            MasterHotelLogHelper::write($log_entry);
        }

        return array(
            'response' => $response,
            'http_code' => $http_code,
            'error' => $error ?: null
        );
    }
}
