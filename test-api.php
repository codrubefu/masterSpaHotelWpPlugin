<?php
/**
 * Test script for hotel rooms API
 * 
 * This script tests the API connection and displays sample data
 * Run this from WordPress admin or via command line to test your API
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If running from command line, define ABSPATH
    if (php_sapi_name() === 'cli') {
        define('ABSPATH', dirname(__FILE__) . '/../../../');
        require_once ABSPATH . 'wp-config.php';
        require_once ABSPATH . 'wp-includes/wp-db.php';
        require_once ABSPATH . 'wp-includes/pluggable.php';
    } else {
        exit;
    }
}

function test_hotel_rooms_api($api_url = 'http://localhost:8082/api/camerehotel') {
    echo "<h2>Testing Hotel Rooms API</h2>\n";
    echo "<p><strong>API URL:</strong> {$api_url}</p>\n";
    
    // Test first page
    $response = wp_remote_get($api_url . '?page=1', array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        )
    ));
    
    if (is_wp_error($response)) {
        echo "<div style='color: red;'><strong>Error:</strong> " . $response->get_error_message() . "</div>\n";
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    echo "<p><strong>Status Code:</strong> {$status_code}</p>\n";
    
    if ($status_code !== 200) {
        echo "<div style='color: red;'><strong>Error:</strong> HTTP {$status_code}</div>\n";
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<div style='color: red;'><strong>JSON Error:</strong> " . json_last_error_msg() . "</div>\n";
        return false;
    }
    
    // Display API info
    echo "<div style='color: green;'><strong>✓ API Connection Successful!</strong></div>\n";
    echo "<h3>API Information:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Current Page:</strong> " . ($data['current_page'] ?? 'N/A') . "</li>\n";
    echo "<li><strong>Last Page:</strong> " . ($data['last_page'] ?? 'N/A') . "</li>\n";
    echo "<li><strong>Per Page:</strong> " . ($data['per_page'] ?? 'N/A') . "</li>\n";
    echo "<li><strong>Total Rooms:</strong> " . ($data['total'] ?? 'N/A') . "</li>\n";
    echo "<li><strong>Rooms in Response:</strong> " . (isset($data['data']) ? count($data['data']) : 0) . "</li>\n";
    echo "</ul>\n";
    
    // Display sample rooms
    if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
        echo "<h3>Sample Rooms (first 3):</h3>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>\n";
        echo "<tr style='background: #f0f0f0;'>\n";
        echo "<th>Room Number</th><th>Type</th><th>Floor</th><th>Max Adults</th><th>Max Kids</th><th>Virtual</th>\n";
        echo "</tr>\n";
        
        $sample_rooms = array_slice($data['data'], 0, 3);
        foreach ($sample_rooms as $room) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($room['nr'] ?? 'N/A') . "</td>\n";
            echo "<td>" . htmlspecialchars($room['tiplung'] ?? 'N/A') . "</td>\n";
            echo "<td>" . htmlspecialchars($room['etajresel'] ?? 'N/A') . "</td>\n";
            echo "<td>" . htmlspecialchars($room['adultMax'] ?? 'N/A') . "</td>\n";
            echo "<td>" . htmlspecialchars($room['kidMax'] ?? 'N/A') . "</td>\n";
            echo "<td>" . ($room['virtual'] ? 'Yes' : 'No') . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Check room types
        $room_types = array();
        foreach ($data['data'] as $room) {
            if (isset($room['tiplung']) && !in_array($room['tiplung'], $room_types)) {
                $room_types[] = $room['tiplung'];
            }
        }
        
        echo "<h3>Room Types Found:</h3>\n";
        echo "<ul>\n";
        foreach ($room_types as $type) {
            echo "<li>" . htmlspecialchars($type) . "</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Test pagination
    if (isset($data['last_page']) && $data['last_page'] > 1) {
        echo "<h3>Testing Pagination:</h3>\n";
        $page_2_response = wp_remote_get($api_url . '?page=2', array('timeout' => 10));
        
        if (!is_wp_error($page_2_response) && wp_remote_retrieve_response_code($page_2_response) === 200) {
            echo "<div style='color: green;'>✓ Page 2 accessible</div>\n";
        } else {
            echo "<div style='color: orange;'>⚠ Page 2 not accessible</div>\n";
        }
    }
    
    return true;
}

// If called directly, run the test
if (php_sapi_name() === 'cli' || (isset($_GET['test_api']) && current_user_can('manage_options'))) {
    $api_url = isset($_GET['api_url']) ? sanitize_url($_GET['api_url']) : 'http://localhost:8082/api/camerehotel';
    test_hotel_rooms_api($api_url);
}

// Add admin page for testing
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Test Hotel API',
        'Test Hotel API',
        'manage_options',
        'test-hotel-api',
        function() {
            $api_url = isset($_GET['api_url']) ? sanitize_url($_GET['api_url']) : 'http://localhost:8082/api/camerehotel';
            ?>
            <div class="wrap">
                <h1>Test Hotel Rooms API</h1>
                <form method="get">
                    <input type="hidden" name="page" value="test-hotel-api">
                    <table class="form-table">
                        <tr>
                            <th scope="row">API URL</th>
                            <td>
                                <input type="url" name="api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text" />
                                <input type="submit" name="test_api" value="Test API" class="button button-primary" />
                            </td>
                        </tr>
                    </table>
                </form>
                
                <?php if (isset($_GET['test_api'])): ?>
                    <hr>
                    <?php test_hotel_rooms_api($api_url); ?>
                <?php endif; ?>
            </div>
            <?php
        }
    );
});
