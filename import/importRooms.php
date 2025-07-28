<?php
/**
 * Hotel Rooms Import Script for WooCommerce
 * 
 * This script imports hotel rooms from an API endpoint and creates WooCommerce products
 * with hotel room-specific attributes and metadata.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HotelRoomsImporter {
    
    private $api_url;
    private $log_messages = [];
    
    public function __construct($api_url = 'http://localhost:8082/api/camerehotel') {
        $this->api_url = $api_url;
        
        // Hook into WordPress admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_import_hotel_rooms', array($this, 'ajax_import_rooms'));
    }
    
    /**
     * Add admin menu for the importer
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Hotel Rooms Import',
            'Import Hotel Rooms',
            'manage_options',
            'hotel-rooms-import',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page for the importer
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Hotel Rooms Import</h1>
            <p>Import hotel rooms from API endpoint to WooCommerce products.</p>
            
            <form id="hotel-rooms-import-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">API URL</th>
                        <td>
                            <input type="url" name="api_url" value="<?php echo esc_attr($this->api_url); ?>" class="regular-text" />
                            <p class="description">
                                The API endpoint URL for hotel rooms data<br>
                                <strong>Common URLs:</strong><br>
                                • <code>http://localhost:8082/api/camerehotel</code> (Local development)<br>
                                • <code>http://127.0.0.1:8082/api/camerehotel</code> (Alternative local)<br>
                                • <code>http://your-domain.com/api/camerehotel</code> (Production)
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Import Options</th>
                        <td>
                            <label>
                                <input type="checkbox" name="update_existing" value="1" checked />
                                Update existing products if room already exists
                            </label><br>
                            <label>
                                <input type="checkbox" name="create_categories" value="1" checked />
                                Create room type categories automatically
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php wp_nonce_field('hotel_rooms_import', 'hotel_rooms_nonce'); ?>
                <p class="submit">
                    <button type="submit" class="button button-primary" id="import-btn">Import Rooms</button>
                </p>
            </form>
            
            <div id="import-progress" style="display: none;">
                <h3>Import Progress</h3>
                <div id="progress-bar" style="width: 100%; background: #f1f1f1; border-radius: 3px;">
                    <div id="progress-fill" style="width: 0%; height: 20px; background: #0073aa; border-radius: 3px; transition: width 0.3s;"></div>
                </div>
                <p id="progress-text">Starting import...</p>
            </div>
            
            <div id="import-results" style="display: none;">
                <h3>Import Results</h3>
                <div id="results-content"></div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#hotel-rooms-import-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $('#import-btn');
                var $progress = $('#import-progress');
                var $results = $('#import-results');
                
                $btn.prop('disabled', true).text('Importing...');
                $progress.show();
                $results.hide();
                
                var formData = $(this).serialize();
                formData += '&action=import_hotel_rooms';
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        importRooms(response.data.total_pages, response.data.api_url, response.data.options);
                    } else {
                        showError(response.data);
                    }
                }).fail(function() {
                    showError('Failed to start import process');
                });
            });
            
            function importRooms(totalPages, apiUrl, options, currentPage = 1) {
                var progress = Math.round((currentPage / totalPages) * 100);
                $('#progress-fill').css('width', progress + '%');
                $('#progress-text').text('Processing page ' + currentPage + ' of ' + totalPages + '...');
                
                $.post(ajaxurl, {
                    action: 'import_hotel_rooms',
                    page: currentPage,
                    api_url: apiUrl,
                    options: options,
                    hotel_rooms_nonce: $('[name="hotel_rooms_nonce"]').val()
                }, function(response) {
                    if (response.success) {
                        if (currentPage < totalPages) {
                            importRooms(totalPages, apiUrl, options, currentPage + 1);
                        } else {
                            showResults(response.data);
                        }
                    } else {
                        showError(response.data);
                    }
                }).fail(function() {
                    showError('Failed to import page ' + currentPage);
                });
            }
            
            function showResults(data) {
                $('#import-btn').prop('disabled', false).text('Import Rooms');
                $('#import-progress').hide();
                $('#import-results').show();
                $('#results-content').html(data.message);
            }
            
            function showError(message) {
                $('#import-btn').prop('disabled', false).text('Import Rooms');
                $('#import-progress').hide();
                $('#import-results').show();
                $('#results-content').html('<div class="notice notice-error"><p>' + message + '</p></div>');
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for room import
     */
    public function ajax_import_rooms() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['hotel_rooms_nonce'], 'hotel_rooms_import')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        try {
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $api_url = isset($_POST['api_url']) ? sanitize_url($_POST['api_url']) : $this->api_url;
            $options = isset($_POST['options']) ? $_POST['options'] : $_POST;
            
            if ($page === 1) {
                // First request - get total pages
                $response = $this->fetch_api_data($api_url, 1);
                if (!$response) {
                    wp_send_json_error('Failed to fetch data from API. Check the import log for details.');
                }
                
                wp_send_json_success(array(
                    'total_pages' => $response['last_page'],
                    'api_url' => $api_url,
                    'options' => $options
                ));
            } else {
                // Import specific page
                $result = $this->import_rooms_from_page($api_url, $page, $options);
                wp_send_json_success($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Import error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fetch data from API endpoint
     */
    private function fetch_api_data($url, $page = 1) {
        $full_url = add_query_arg('page', $page, $url);
        
        $this->log('Attempting to connect to: ' . $full_url);
        
        $response = wp_remote_get($full_url, array(
            'timeout' => 30,
            'sslverify' => false, // Disable SSL verification for local development
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Secret' => 'your-very-secure-secret-key-here'
            )
        ));
     
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $error_code = $response->get_error_code();
            $this->log('API Error (' . $error_code . '): ' . $error_message);
            
            // Provide more specific error messages
            if (strpos($error_message, 'Failed to connect') !== false) {
                $this->log('Connection failed - please check if the API server is running on port 8082');
            } elseif (strpos($error_message, 'cURL error 7') !== false) {
                $this->log('cURL connection error - the API endpoint may not be accessible');
            }
            
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $this->log('API Response Code: ' . $response_code);
        
        if ($response_code !== 200) {
            $this->log('API returned non-200 status code: ' . $response_code);
            $response_message = wp_remote_retrieve_response_message($response);
            $this->log('Response message: ' . $response_message);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $this->log('Response received, body length: ' . strlen($body) . ' characters');
        
        if (empty($body)) {
            $this->log('Empty response body received from API');
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('JSON decode error: ' . json_last_error_msg());
            return false;
        }
        
        return $data;
    }
    
    /**
     * Import rooms from a specific page
     */
    private function import_rooms_from_page($api_url, $page, $options) {
        $data = $this->fetch_api_data($api_url, $page);
        
        if (!$data || !isset($data['data'])) {
            return array('message' => 'No data found for page ' . $page);
        }
        
        $imported = 0;
        $updated = 0;
        $errors = 0;
        
        foreach ($data['data'] as $room_data) {
            try {
                $result = $this->create_or_update_room_product($room_data, $options);
                if ($result['created']) {
                    $imported++;
                } elseif ($result['updated']) {
                    $updated++;
                }
            } catch (Exception $e) {
                $errors++;
                $this->log('Error importing room ' . $room_data['nr'] . ': ' . $e->getMessage());
            }
        }
        
        if ($page === $data['last_page']) {
            // Final page - return summary
            return array(
                'message' => sprintf(
                    '<div class="notice notice-success"><p>Import completed! Imported: %d, Updated: %d, Errors: %d</p></div>%s',
                    $imported,
                    $updated,
                    $errors,
                    $this->get_log_output()
                )
            );
        }
        
        return array(
            'imported' => $imported,
            'updated' => $updated,
            'errors' => $errors
        );
    }
    
    /**
     * Create or update a WooCommerce product for a hotel room
     */
    private function create_or_update_room_product($room_data, $options) {
        // Check if product already exists by room number
        $existing_product = $this->find_existing_room_product($room_data['nr']);
        
        $product_id = null;
        $created = false;
        $updated = false;
        
        if ($existing_product && !isset($options['update_existing'])) {
            // Product exists and update is not enabled
            return array('created' => false, 'updated' => false, 'product_id' => $existing_product->ID);
        }
        
        // Prepare product data
        $product_data = array(
            'post_title' => sprintf('Room %s - %s', $room_data['nr'], $room_data['tiplung']),
            'post_content' => $this->generate_room_description($room_data),
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => array(
                '_visibility' => 'visible',
                '_stock_status' => 'instock',
                '_manage_stock' => 'yes',
                '_stock' => 1, // Only one room available
                '_virtual' => $room_data['virtual'] ? 'yes' : 'no',
                '_sold_individually' => 'yes',
                
                // Hotel room specific metadata
                '_hotel_room_number' => $room_data['nr'],
                '_hotel_room_id' => $room_data['idcamerehotel'],
                '_hotel_room_type' => $room_data['tiplung'],
                '_hotel_room_floor' => $room_data['etajresel'],
                '_hotel_adults_max' => $room_data['adultMax'],
                '_hotel_kids_max' => $room_data['kidMax'],
                '_hotel_baby_bed' => $room_data['babyBed'],
                '_hotel_bed_info' => $room_data['bed'],
                '_hotel_virtual' => $room_data['virtual'] ? 'yes' : 'no',
                '_hotel_id' => $room_data['idhotel'],
                '_hotel_label_id' => $room_data['idlabel'],
            )
        );
        
        if ($existing_product) {
            // Update existing product
            $product_data['ID'] = $existing_product->ID;
            $product_id = wp_update_post($product_data);
            $updated = true;
        } else {
            // Create new product
            $product_id = wp_insert_post($product_data);
            $created = true;
        }
        
        if (is_wp_error($product_id)) {
            throw new Exception('Failed to create/update product: ' . $product_id->get_error_message());
        }
        
        // Set product category based on room type
        if (isset($options['create_categories'])) {
            $this->set_room_category($product_id, $room_data['tiplung']);
        }
        
        // Set product attributes
        $this->set_room_attributes($product_id, $room_data);
        
        $this->log(sprintf(
            '%s room %s (ID: %d) - %s',
            $created ? 'Created' : 'Updated',
            $room_data['nr'],
            $product_id,
            $room_data['tiplung']
        ));
        
        return array(
            'created' => $created,
            'updated' => $updated,
            'product_id' => $product_id
        );
    }
    
    /**
     * Find existing room product by room number
     */
    private function find_existing_room_product($room_number) {
        $posts = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_hotel_room_number',
            'meta_value' => $room_number,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ));
        
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Generate room description
     */
    private function generate_room_description($room_data) {
        $description = sprintf('<h3>%s - Room %s</h3>', $room_data['tiplung'], $room_data['nr']);
        $description .= '<ul>';
        $description .= sprintf('<li><strong>Room Type:</strong> %s</li>', $room_data['tiplung']);
        $description .= sprintf('<li><strong>Floor:</strong> %d</li>', $room_data['etajresel']);
        $description .= sprintf('<li><strong>Maximum Adults:</strong> %d</li>', $room_data['adultMax']);
        $description .= sprintf('<li><strong>Maximum Children:</strong> %d</li>', $room_data['kidMax']);
        
        if ($room_data['babyBed']) {
            $description .= sprintf('<li><strong>Baby Bed:</strong> %s</li>', $room_data['babyBed']);
        }
        
        if ($room_data['bed']) {
            $description .= sprintf('<li><strong>Bed Information:</strong> %s</li>', $room_data['bed']);
        }
        
        if ($room_data['virtual']) {
            $description .= '<li><strong>Virtual Room:</strong> Yes</li>';
        }
        
        $description .= '</ul>';
        
        return $description;
    }
    
    /**
     * Set room category based on room type
     */
    private function set_room_category($product_id, $room_type) {
        // Create or get category
        $category = get_term_by('name', $room_type, 'product_cat');
        
        if (!$category) {
            $category_result = wp_insert_term($room_type, 'product_cat');
            if (!is_wp_error($category_result)) {
                $category_id = $category_result['term_id'];
            }
        } else {
            $category_id = $category->term_id;
        }
        
        if (isset($category_id)) {
            wp_set_post_terms($product_id, array($category_id), 'product_cat');
        }
    }
    
    /**
     * Set product attributes for room
     */
    private function set_room_attributes($product_id, $room_data) {
        $attributes = array();
        
        // Room Number attribute
        $attributes['room-number'] = array(
            'name' => 'Room Number',
            'value' => $room_data['nr'],
            'position' => 0,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        );
        
        // Room Type attribute
        $attributes['room-type'] = array(
            'name' => 'Room Type',
            'value' => $room_data['tiplung'],
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        );
        
        // Floor attribute
        $attributes['floor'] = array(
            'name' => 'Floor',
            'value' => $room_data['etajresel'],
            'position' => 2,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        );
        
        // Maximum Adults attribute
        $attributes['max-adults'] = array(
            'name' => 'Maximum Adults',
            'value' => $room_data['adultMax'],
            'position' => 3,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        );
        
        // Maximum Children attribute
        $attributes['max-children'] = array(
            'name' => 'Maximum Children',
            'value' => $room_data['kidMax'],
            'position' => 4,
            'is_visible' => 1,
            'is_variation' => 0,
            'is_taxonomy' => 0
        );
        
        update_post_meta($product_id, '_product_attributes', $attributes);
    }
    
    /**
     * Log messages
     */
    private function log($message) {
        $this->log_messages[] = '[' . current_time('mysql') . '] ' . $message;
    }
    
    /**
     * Get formatted log output
     */
    private function get_log_output() {
        if (empty($this->log_messages)) {
            return '';
        }
        
        return '<h4>Import Log:</h4><pre style="background: #f1f1f1; padding: 10px; border-radius: 3px; max-height: 300px; overflow-y: auto;">' . 
               implode("\n", $this->log_messages) . '</pre>';
    }
    
    /**
     * Manual import function (can be called programmatically)
     */
    public function manual_import($api_url = null, $update_existing = true, $create_categories = true) {
        if ($api_url) {
            $this->api_url = $api_url;
        }
        
        $options = array(
            'update_existing' => $update_existing,
            'create_categories' => $create_categories
        );
        
        // Get first page to determine total pages
        $first_page_data = $this->fetch_api_data($this->api_url, 1);
        if (!$first_page_data) {
            return false;
        }
        
        $total_imported = 0;
        $total_updated = 0;
        $total_errors = 0;
        
        // Import all pages
        for ($page = 1; $page <= $first_page_data['last_page']; $page++) {
            $result = $this->import_rooms_from_page($this->api_url, $page, $options);
            if (isset($result['imported'])) {
                $total_imported += $result['imported'];
                $total_updated += $result['updated'];
                $total_errors += $result['errors'];
            }
        }
        
        return array(
            'imported' => $total_imported,
            'updated' => $total_updated,
            'errors' => $total_errors,
            'log' => $this->log_messages
        );
    }
}

// Initialize the importer
new HotelRoomsImporter();

/**
 * Helper function to run import programmatically
 * Usage: import_hotel_rooms('http://localhost:8082/api/camerehotel');
 */
function import_hotel_rooms($api_url = 'http://localhost:8082/api/camerehotel', $update_existing = true, $create_categories = true) {
    $importer = new HotelRoomsImporter();
    return $importer->manual_import($api_url, $update_existing, $create_categories);
}
