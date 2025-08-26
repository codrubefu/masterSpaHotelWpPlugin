<?php
/**
 * Hotel Room Search Integration
 * 
 * This class handles searching for room combinations via API and displaying
 * corresponding WooCommerce products
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit;
}

$helper_path = dirname(__FILE__).'/includes/MasterHotelCurlHelper.php';
if (file_exists($helper_path)) {
    require_once $helper_path;
}
class HotelRoomSearcher {
    
    private $api_url;
    private $api_secret;
    
    public function __construct() {

        // Use helper for cURL
       
     
        

        // Get configuration from admin settings
        $this->api_url = MasterHotelConfig::get_config('search_api_url', 'http://laravel-app/api/rooms/search-combinations');
        $this->api_secret = MasterHotelConfig::get_config('api_secret', 'your-very-secure-secret-key-here');
        
        // Debug: Log that class is being instantiated
        error_log('HotelRoomSearcher class instantiated with API URL: ' . $this->api_url);
        
        // Hook into WordPress
        add_action('wp_ajax_search_hotel_rooms', array($this, 'ajax_search_rooms'));
        add_action('wp_ajax_nopriv_search_hotel_rooms', array($this, 'ajax_search_rooms'));
        add_shortcode('hotel_room_availability_search', array($this, 'render_search_form'));

        // Enqueue CSS/JS for the plugin
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Debug: Log that shortcode is registered
        error_log('Hotel search shortcode registered');
    
    }

    /**
     * Enqueue plugin CSS and JS
     */
    public function enqueue_assets() {
        $plugin_url = plugin_dir_url(__FILE__);
        wp_enqueue_style('master-hotel-room-search', $plugin_url . 'assets/room-search.css', array(), '1.0');
         wp_enqueue_style('master-hotel-room-search-paradise', $plugin_url . 'assets/paradise.css', array(), '1.0');
        wp_enqueue_script('master-hotel-room-search', $plugin_url . 'assets/room-search.js', array('jquery'), '1.0', true);
        // Localize script for AJAX URL and nonce
        wp_localize_script('master-hotel-room-search', 'hotelRoomSearchVars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('hotel_search_nonce'),
        ));
    }
    
    /**
     * Render the search form shortcode
     */
    public function render_search_form($atts = array()) {
        // Debug: Log that shortcode is being called
        error_log('Hotel search shortcode called with attributes: ' . print_r($atts, true));
        
        $atts = shortcode_atts(array(
            'show_title' => true,
            'title' => 'Căutare disponibilitate camere hotel'
        ), $atts);
        
        // Debug: Log processed attributes
        error_log('Hotel search processed attributes: ' . print_r($atts, true));
        
        ob_start();
        ?>
        <div id="hotel-availability-search">
            <?php if ($atts['show_title']): ?>
                <h3><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>
            
            <form id="hotel-availability-form">
                <div class="search-row">
                    <div class="search-field">
                        <label for="start_date">Data check-in:</label>
                        <input type="date" value="<?php echo esc_attr(date('Y-m-d')); ?>" name="start_date" id="start_date" required>
                    </div>
                    
                    <div class="search-field">
                        <label for="end_date">Data check-out:</label>
                        <input type="date" value="<?php echo esc_attr(date('Y-m-d', strtotime('+1 day'))); ?>" name="end_date" id="end_date" required>
                    </div>
                </div>
                
                <div class="search-row">
                    <div class="search-field">
                        <label for="adults">Adulți:</label>
                        <select name="adults" id="adults" required>
                            <?php 
                            $default_adults = MasterHotelConfig::get_config('default_adults', 2);
                            $max_adults = MasterHotelConfig::get_config('max_adults', 8);
                            for ($i = 1; $i <= $max_adults; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, $default_adults); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="search-field">
                        <label for="kids">Copii:</label>
                        <select name="kids" id="kids">
                            <?php 
                            $default_children = MasterHotelConfig::get_config('default_children', 0);
                            $max_children = MasterHotelConfig::get_config('max_children', 4);
                            for ($i = 0; $i <= $max_children; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, $default_children); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="search-field">
                        <label for="number_of_rooms">Număr de camere:</label>
                        <select name="number_of_rooms" id="number_of_rooms" required>
                            <?php 
                            $max_rooms = MasterHotelConfig::get_config('max_rooms', 5);
                            for ($i = 1; $i <= $max_rooms; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, 1); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="search-btn">Caută camere disponibile</button>
                </div>
            </form>
            
            <div id="search-loading" style="display: none;">
                <p>Se caută camere disponibile...</p>
            </div>
            
            <div id="search-results"></div>
    </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for room search
     */
    public function ajax_search_rooms() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hotel_search_nonce')) {
            wp_send_json_error('Verificare de securitate eșuată');
        }
        
        try {
            $search_params = array(
                'adults' => intval($_POST['adults']),
                'kids' => intval($_POST['kids']),
                'number_of_rooms' => $_POST['number_of_rooms'],
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date'])
            );
            // Add page parameter if present
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $search_params['page'] = $page;

            // Save search params in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['hotel_search_params'] = $search_params;

            // Validate dates
            if (strtotime($search_params['start_date']) >= strtotime($search_params['end_date'])) {
                wp_send_json_error('Data de check-out trebuie să fie după data de check-in');
            }

            if (strtotime($search_params['start_date']) < strtotime('today')) {
                wp_send_json_error('Data de check-in nu poate fi în trecut');
            }


            // Search for combinations
            $api_response = $this->search_room_combinations($search_params);

            // Expecting $api_response['data'] to have 'data' and 'pagination' keys
            if ($api_response === false || !isset($api_response) || !isset($api_response['pagination'])) {
                wp_send_json_error('Eroare la căutarea combinațiilor de camere. Vă rugăm să încercați din nou.');
            }

            $combinations_data = $api_response['data'];
            $pagination = $api_response['data']['pagination'];
            $search_params_return = isset($api_response['data']['search_params']) ? $api_response['data']['search_params'] : $search_params;

            // Enhance combinations with product data
            $enhanced_combinations = array();
            foreach ($combinations_data as $combo) {
                $enhanced_combinations[] = $this->enhance_combinations_with_products([$combo])[0];
            }

            wp_send_json_success(array(
                'combinations' => $enhanced_combinations,
                'pagination' => $pagination,
                'search_params' => $search_params_return
            ));

        } catch (Exception $e) {
            wp_send_json_error('Eroare de căutare: ' . $e->getMessage());
        }
    }
    

    /**
     * Search for room combinations via API
     */
    private function search_room_combinations($params) {
        // Ensure params are properly formatted
        $json_params = json_encode($params);
       
        // Log the request for debugging
        error_log('Hotel search API request to: ' . $this->api_url);
        error_log('Hotel search API params: ' . $json_params);


        $params_array = $params;
        try {
              $curl_response = MasterHotelCurlHelper::post_json($this->api_url, $params_array, $headers);
        } catch (Exception $e) {
            error_log('Hotel search API JSON decode error: ' . $e->getMessage());
            return false;
        }
        if (!empty($curl_response['error'])) {
            error_log('Hotel search API error: ' . $curl_response['error']);
            return false;
        }
  
        $response_code = $curl_response['http_code'];
       
        error_log('Hotel search API response code: ' . $response_code);

        if ($response_code !== 200) {
            error_log('Hotel search API returned status: ' . $response_code);
            return false;
        }

        $response_body = $curl_response['response'];
        error_log('Hotel search API response body: ' . substr($response_body, 0, 500));

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Hotel search JSON decode error: ' . json_last_error_msg());
            return false;
        }

        return $data;
    }
    
    /**
     * Enhance combinations with WooCommerce product data
     */
    private function enhance_combinations_with_products($combinations) {
        if (!$combinations || !is_array($combinations)) {
            return array();
        }

        foreach ($combinations as $room_type => &$type_data) {
            if (!isset($type_data['combo']) || !is_array($type_data['combo'])) {
                continue;
            }
     
            foreach ($type_data['combo'] as &$combo) {
                if (!is_array($combo)) {
                    continue;
                }

                foreach ($combo as &$room) {
                    if (!isset($room['nr'])) {
                        continue;
                    }

                    // Find corresponding WooCommerce product
                    $product = $this->find_product_by_room_type($room['type']);
                    $related_article_id = get_post_meta($product->ID, '_related_article_id', true);
                    // Only query for related article if not already set
                    $related_article = $this->find_related_article_id_by_room_type($related_article_id);
                    if ($related_article) {
                        $room['_related_article'] = $related_article;
                    }

                    if ($product) {
                        $room['product_id'] = $product->ID;
                        $room['product_url'] = get_permalink($room['_related_article']->ID) ? get_permalink($room['_related_article']->ID) : get_permalink($product->ID);
                        $room['product_title'] =  $room['_related_article']->post_title ? $room['_related_article']->post_title : $room['_related_article']->post_title;
                        $room['product_price'] = get_post_meta($product->ID, '_price', true);
                        $room['description'] = $product->post_content;
                        // Get product image
                        if( get_post_thumbnail_id($room['_related_article']->ID) ) {
                            $image_id = get_post_thumbnail_id($room['_related_article']->ID);
                            if ($image_id) {
                                $image_url = wp_get_attachment_image_url($image_id, 'medium');
                                $room['product_image'] = str_replace('http://localhost:8090', 'https://resortparadis.ro', $image_url);
                            }
                        }else {
                            $image_url = wp_get_attachment_image_url(get_post_thumbnail_id($product->ID), 'medium');
                            $room['product_image'] = str_replace('http://localhost:8082', 'https://resortparadis.ro', $image_url);
                        }

                        // Get all variations for this product (if variable)
                        if ('product_variation' === get_post_type($product->ID) || get_post_type($product->ID) === 'product') {
                            $product_obj = wc_get_product($product->ID);
                            $cheapest = null;
                            if ($product_obj && $product_obj->is_type('variable')) {
                                $variations = $product_obj->get_available_variations();
                                $room['variations'] = array();
                                foreach ($variations as $variation) {
                                    $room['variations'][] = array(
                                        'variation_id' => $variation['variation_id'],
                                        'attributes' => $variation['attributes'],
                                        'price' => $variation['display_price'],
                                        'regular_price' => $variation['display_regular_price'],
                                        'sale_price' => $variation['display_price'] < $variation['display_regular_price'] ? $variation['display_price'] : '',
                                        'in_stock' => $variation['is_in_stock'],
                                        'sku' => $variation['sku'],
                                        'image' => isset($variation['image']['src']) ? $variation['image']['src'] : '',
                                    );
                                    if ($cheapest === null || $variation['display_price'] < $cheapest) {
                                        $cheapest = $variation['display_price'];
                                    }
                                }
                            }
                            if ($cheapest !== null) {
                                $room['cheapest_price'] = $cheapest;
                            } else {
                                $room['cheapest_price'] = $room['product_price'] * 0.8; // fallback
                            }
                        } else {
                            $room['cheapest_price'] = $room['product_price'] * 0.8; // fallback
                        }
                    }
                }
            }
        }
        // Calculate priceCombo for all combinations
        $this->calculate_price_combo($combinations);
        return $this->sortCombinationsByPriceCombo($combinations);
    }

    /**
     * Find related article ID by product ID
     *
     * @param int $product_id
     * @return int|null
     */
    private function find_related_article_id_by_room_type($product_id) {

        // Directly get the post with ID = $product_id
        $post = get_post($product_id);
        if ($post && $post->post_status === 'publish' && $post->post_type === 'loftocean_room') {
            return $post;
        }
        return null;
    }
    /**
     * Calculate priceCombo for each room_type in combinations
     */
    private function calculate_price_combo(&$combinations) {
        foreach ($combinations as &$type_data) {
            if (!isset($type_data['combo']) || !is_array($type_data['combo'])) {
                continue;
            }
            $type_data['priceCombo'] = array();
            foreach ($type_data['combo'] as $combo) {
                $combo_sum = 0;
                foreach ($combo as $room) {
                    if (isset($room['cheapest_price'])) {
                        $combo_sum += floatval($room['cheapest_price']);
                    }
                }
                $type_data['priceCombo'] = (int)$combo_sum;
            }
        }
    }

    /**
     * Sorts the combinations array by priceCombo ascending (cheapest first)
     *
     * @param array $combinations The combinations array to sort
     * @return array The sorted combinations array
     */
    function sortCombinationsByPriceCombo(array $combinations): array
    {
        uasort($combinations, function ($a, $b) {
            $aPrice = is_array($a['priceCombo']) ? min($a['priceCombo']) : (is_numeric($a['priceCombo']) ? $a['priceCombo'] : PHP_FLOAT_MAX);
            $bPrice = is_array($b['priceCombo']) ? min($b['priceCombo']) : (is_numeric($b['priceCombo']) ? $b['priceCombo'] : PHP_FLOAT_MAX);
            return $aPrice <=> $bPrice;
        });
        return $combinations;
    }


    
    
    /**
     * Find WooCommerce product by room type
     */
    private function find_product_by_room_type($room_tip) {
        // Now search by room type (tip) instead of room number
        $posts = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_hotel_room_type',
            'meta_value' => $room_tip, // $room_tip now represents the type
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        return !empty($posts) ? $posts[0] : null;
    }
    
    /**
     * Get room availability for specific dates (helper function)
     */
    public function get_room_availability($room_number, $start_date, $end_date) {
        // This can be extended to check actual booking calendar
        // For now, we'll assume rooms are available if they exist as products
        $product = $this->find_product_by_room_number($room_number);
        
        if (!$product) {
            return false;
        }
        
        // Check if product is in stock
        $stock_status = get_post_meta($product->ID, '_stock_status', true);
        return $stock_status === 'instock';
    }
}

// Initialize the room searcher
new HotelRoomSearcher();

// Add a simple test shortcode to verify shortcodes are working
add_shortcode('test_shortcode', function($atts) {
    error_log('Test shortcode called');
    return '<div style="background: red; color: white; padding: 10px;">TEST SHORTCODE WORKING</div>';
});

// Add debug function to check registered shortcodes
add_action('init', function() {
    global $shortcode_tags;
    error_log('Registered shortcodes: ' . implode(', ', array_keys($shortcode_tags)));
});
