<?php
/**
 * MasterHotel Configuration Admin Page
 * 
 * This file handles the admin configuration page for MasterHotel plugin settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class MasterHotelConfig {
    public function add_log_admin_menu() {
        add_submenu_page(
            'options-general.php',
            'MasterHotel Log',
            'MasterHotel Log',
            'manage_options',
            'masterhotel-log',
            array($this, 'log_admin_page')
        );
    }

    public function log_admin_page() {
        if (!class_exists('MasterHotelLogHelper')) {
            $log_path = dirname(__FILE__) . '/includes/MasterHotelLogHelper.php';
            if (file_exists($log_path)) {
                require_once $log_path;
            }
        }
        if (isset($_POST['masterhotel_clear_log']) && check_admin_referer('masterhotel_clear_log')) {
            MasterHotelLogHelper::clear_log();
            echo '<div class="updated"><p>Log cleared.</p></div>';
        }
        $log = class_exists('MasterHotelLogHelper') ? MasterHotelLogHelper::get_log() : '';
        echo '<div class="wrap"><h1>MasterHotel Log</h1>';
        echo '<form method="post">';
        wp_nonce_field('masterhotel_clear_log');
        echo '<textarea readonly rows="25" style="width:100%;font-family:monospace;">' . esc_textarea($log) . '</textarea><br><br>';
        echo '<input type="submit" name="masterhotel_clear_log" class="button button-secondary" value="Clear Log" onclick="return confirm(\'Are you sure you want to clear the log?\');">';
        echo '</form></div>';
    }
    
    private $option_group = 'masterhotel_settings';
    private $option_name = 'masterhotel_options';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu for configuration
     */
    public function add_admin_menu() {
        add_options_page(
            'MasterHotel Settings',
            'MasterHotel',
            'manage_options',
            'masterhotel-config',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting($this->option_group, $this->option_name, array($this, 'sanitize_settings'));
        
        // API Settings Section
        add_settings_section(
            'api_settings',
            'API Configuration',
            array($this, 'api_settings_callback'),
            'masterhotel-config'
        );
        
        // Room Import API URL
        add_settings_field(
            'import_api_url',
            'Room Import API URL',
            array($this, 'import_api_url_callback'),
            'masterhotel-config',
            'api_settings'
        );
        
        // Room Search API URL
        add_settings_field(
            'search_api_url',
            'Room Search API URL',
            array($this, 'search_api_url_callback'),
            'masterhotel-config',
            'api_settings'
        );
        

         // Order Completed Webhook URL
        add_settings_field(
            'order_completed_webhook_url',
            'Order Completed Webhook URL',
            array($this, 'order_webhook_url_callback'),
            'masterhotel-config',
            'api_settings'
        );

        // API Secret Key
        add_settings_field(
            'api_secret',
            'API Secret Key',
            array($this, 'api_secret_callback'),
            'masterhotel-config',
            'api_settings'
        );
        
        // General Settings Section
        add_settings_section(
            'general_settings',
            'General Settings',
            array($this, 'general_settings_callback'),
            'masterhotel-config'
        );
        
        // Enable Debug Logging
        add_settings_field(
            'enable_debug',
            'Enable Debug Logging',
            array($this, 'enable_debug_callback'),
            'masterhotel-config',
            'general_settings'
        );
        
        // Auto-create Categories
        add_settings_field(
            'auto_create_categories',
            'Auto-create Room Categories',
            array($this, 'auto_create_categories_callback'),
            'masterhotel-config',
            'general_settings'
        );
        
        // Update Existing Products
        add_settings_field(
            'update_existing',
            'Update Existing Products',
            array($this, 'update_existing_callback'),
            'masterhotel-config',
            'general_settings'
        );
        
        // Search Form Settings Section
        add_settings_section(
            'search_settings',
            'Search Form Settings',
            array($this, 'search_settings_callback'),
            'masterhotel-config'
        );
        
        // Default Adults
        add_settings_field(
            'default_adults',
            'Default Adults',
            array($this, 'default_adults_callback'),
            'masterhotel-config',
            'search_settings'
        );
        
        // Default Children
        add_settings_field(
            'default_children',
            'Default Children',
            array($this, 'default_children_callback'),
            'masterhotel-config',
            'search_settings'
        );
        
        // Max Adults
        add_settings_field(
            'max_adults',
            'Maximum Adults',
            array($this, 'max_adults_callback'),
            'masterhotel-config',
            'search_settings'
        );
        
        // Max Children
        add_settings_field(
            'max_children',
            'Maximum Children',
            array($this, 'max_children_callback'),
            'masterhotel-config',
            'search_settings'
        );
        
        // Max Rooms
        add_settings_field(
            'max_rooms',
            'Maximum Rooms',
            array($this, 'max_rooms_callback'),
            'masterhotel-config',
            'search_settings'
        );


    }

    public function order_webhook_url_callback() {
        $value = $this->get_option('order_completed_webhook_url');
        echo '<input type="url" name="' . $this->option_name . '[order_completed_webhook_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL for sending order data when status changes to completed.</p>';
    }
    
    
    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['import_api_url'])) {
            $sanitized['import_api_url'] = esc_url_raw($input['import_api_url']);
        }

        if (isset($input['search_api_url'])) {
            $sanitized['search_api_url'] = esc_url_raw($input['search_api_url']);
        }

        if (isset($input['order_completed_webhook_url'])) {
            $sanitized['order_completed_webhook_url'] = esc_url_raw($input['order_completed_webhook_url']);
        }

        if (isset($input['api_secret'])) {
            $sanitized['api_secret'] = sanitize_text_field($input['api_secret']);
        }

        if (isset($input['enable_debug'])) {
            $sanitized['enable_debug'] = (bool) $input['enable_debug'];
        }

        if (isset($input['auto_create_categories'])) {
            $sanitized['auto_create_categories'] = (bool) $input['auto_create_categories'];
        }

        if (isset($input['update_existing'])) {
            $sanitized['update_existing'] = (bool) $input['update_existing'];
        }

        if (isset($input['default_adults'])) {
            $sanitized['default_adults'] = intval($input['default_adults']);
        }

        if (isset($input['default_children'])) {
            $sanitized['default_children'] = intval($input['default_children']);
        }

        if (isset($input['max_adults'])) {
            $sanitized['max_adults'] = intval($input['max_adults']);
        }

        if (isset($input['max_children'])) {
            $sanitized['max_children'] = intval($input['max_children']);
        }

        if (isset($input['max_rooms'])) {
            $sanitized['max_rooms'] = intval($input['max_rooms']);
        }

        return $sanitized;
    }
    
    /**
     * Get option value with default
     */
    public function get_option($key, $default = '') {
        $options = get_option($this->option_name, array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Get all options as array (for use in other classes)
     */
    public static function get_all_options() {
        return get_option('masterhotel_options', array());
    }
    
    /**
     * Get specific option value with default (static method for use in other classes)
     */
    public static function get_config($key, $default = '') {
        $options = get_option('masterhotel_options', array());
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>MasterHotel Settings</h1>
            <p>Configure your MasterHotel plugin settings below.</p>
            
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections('masterhotel-config');
                submit_button();
                ?>
            </form>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Quick Actions</h2>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=hotel-rooms-import'); ?>" class="button button-primary">Import Hotel Rooms</a>
                    <a href="<?php echo admin_url('tools.php?page=test-hotel-api'); ?>" class="button button-secondary">Test API Connection</a>
                </p>
            </div>
            
            <div class="card" style="margin-top: 20px;">
                <h2>Shortcode Usage</h2>
                <p>Use this shortcode to display the room availability search form:</p>
                <code>[hotel_room_availability_search]</code>
                
                <h4>Shortcode Parameters:</h4>
                <ul>
                    <li><code>show_title="true/false"</code> - Show/hide the form title</li>
                    <li><code>title="Your Title"</code> - Custom title for the form</li>
                </ul>
                
                <h4>Examples:</h4>
                <ul>
                    <li><code>[hotel_room_availability_search]</code> - Default form</li>
                    <li><code>[hotel_room_availability_search title="Check Availability"]</code> - Custom title</li>
                    <li><code>[hotel_room_availability_search show_title="false"]</code> - No title</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    // Section callbacks
    public function api_settings_callback() {
        echo '<p>Configure the API endpoints and authentication for room management.</p>';
    }
    
    public function general_settings_callback() {
        echo '<p>General plugin behavior and default settings.</p>';
    }
    
    public function search_settings_callback() {
        echo '<p>Configure the room search form defaults and limits.</p>';
    }
    
    // Field callbacks
    public function import_api_url_callback() {
        $value = $this->get_option('import_api_url', 'http://localhost:8082/api/camerehotel/grouped-by-type');
        echo '<input type="url" name="' . $this->option_name . '[import_api_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL for importing hotel rooms (grouped by type endpoint)</p>';
    }
    
    public function search_api_url_callback() {
        $value = $this->get_option('search_api_url');
        echo '<input type="url" name="' . $this->option_name . '[search_api_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL for searching room combinations</p>';
    }
    
    public function api_secret_callback() {
        $value = $this->get_option('api_secret', 'your-very-secure-secret-key-here');
        echo '<input type="password" name="' . $this->option_name . '[api_secret]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Secret key for API authentication (X-API-Secret header)</p>';
    }
    
    public function enable_debug_callback() {
        $value = $this->get_option('enable_debug', true);
        echo '<label><input type="checkbox" name="' . $this->option_name . '[enable_debug]" value="1" ' . checked(1, $value, false) . ' /> Enable debug logging</label>';
        echo '<p class="description">Log API requests and responses for debugging</p>';
    }
    
    public function auto_create_categories_callback() {
        $value = $this->get_option('auto_create_categories', true);
        echo '<label><input type="checkbox" name="' . $this->option_name . '[auto_create_categories]" value="1" ' . checked(1, $value, false) . ' /> Auto-create room type categories</label>';
        echo '<p class="description">Automatically create WooCommerce categories for room types during import</p>';
    }
    
    public function update_existing_callback() {
        $value = $this->get_option('update_existing', true);
        echo '<label><input type="checkbox" name="' . $this->option_name . '[update_existing]" value="1" ' . checked(1, $value, false) . ' /> Update existing products</label>';
        echo '<p class="description">Update existing room products when importing (if unchecked, existing rooms will be skipped)</p>';
    }
    
    public function default_adults_callback() {
        $value = $this->get_option('default_adults', 2);
        echo '<select name="' . $this->option_name . '[default_adults]">';
        for ($i = 1; $i <= 8; $i++) {
            echo '<option value="' . $i . '" ' . selected($i, $value, false) . '>' . $i . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Default number of adults in the search form</p>';
    }
    
    public function default_children_callback() {
        $value = $this->get_option('default_children', 0);
        echo '<select name="' . $this->option_name . '[default_children]">';
        for ($i = 0; $i <= 4; $i++) {
            echo '<option value="' . $i . '" ' . selected($i, $value, false) . '>' . $i . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Default number of children in the search form</p>';
    }
    
    public function max_adults_callback() {
        $value = $this->get_option('max_adults', 8);
        echo '<input type="number" name="' . $this->option_name . '[max_adults]" value="' . esc_attr($value) . '" min="1" max="20" />';
        echo '<p class="description">Maximum number of adults selectable in the search form</p>';
    }
    
    public function max_children_callback() {
        $value = $this->get_option('max_children', 4);
        echo '<input type="number" name="' . $this->option_name . '[max_children]" value="' . esc_attr($value) . '" min="0" max="10" />';
        echo '<p class="description">Maximum number of children selectable in the search form</p>';
    }
    
    public function max_rooms_callback() {
        $value = $this->get_option('max_rooms', 5);
        echo '<input type="number" name="' . $this->option_name . '[max_rooms]" value="' . esc_attr($value) . '" min="1" max="10" />';
        echo '<p class="description">Maximum number of rooms selectable in the search form</p>';
    }
}
// Initialize the configuration
new MasterHotelConfig();
?>
