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

class HotelRoomSearcher {
    
    private $api_url;
    private $api_secret;
    
    public function __construct($api_url = 'http://laravel-app/api/rooms/search-combinations', $api_secret = 'your-very-secure-secret-key-here') {
        $this->api_url = $api_url;
        $this->api_secret = $api_secret;
        
        // Debug: Log that class is being instantiated
        error_log('HotelRoomSearcher class instantiated');
        
        // Hook into WordPress
        add_action('wp_ajax_search_hotel_rooms', array($this, 'ajax_search_rooms'));
        add_action('wp_ajax_nopriv_search_hotel_rooms', array($this, 'ajax_search_rooms'));
        add_shortcode('hotel_room_availability_search', array($this, 'render_search_form'));
        
        // Debug: Log that shortcode is registered
        error_log('Hotel search shortcode registered');
    }
    
    /**
     * Render the search form shortcode
     */
    public function render_search_form($atts = array()) {
        // Debug: Log that shortcode is being called
        error_log('Hotel search shortcode called with attributes: ' . print_r($atts, true));
        
        $atts = shortcode_atts(array(
            'show_title' => true,
            'title' => 'Hotel Room Availability Search'
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
                        <label for="start_date">Check-in Date:</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>
                    
                    <div class="search-field">
                        <label for="end_date">Check-out Date:</label>
                        <input type="date" name="end_date" id="end_date" required>
                    </div>
                </div>
                
                <div class="search-row">
                    <div class="search-field">
                        <label for="adults">Adults:</label>
                        <select name="adults" id="adults" required>
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, 2); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="search-field">
                        <label for="kids">Children:</label>
                        <select name="kids" id="kids">
                            <?php for ($i = 0; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, 0); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="search-field">
                        <label for="number_of_rooms">Number of Rooms:</label>
                        <select name="number_of_rooms" id="number_of_rooms" required>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($i, 1); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="search-actions">
                    <button type="submit" class="search-btn">Search Available Rooms</button>
                </div>
            </form>
            
            <div id="search-loading" style="display: none;">
                <p>Searching for available rooms...</p>
            </div>
            
            <div id="search-results"></div>
        </div>
        
        <style>
        #hotel-availability-search {
            max-width: 800px;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        
        .search-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .search-field {
            flex: 1;
            min-width: 150px;
        }
        
        .search-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .search-field input,
        .search-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        .search-actions {
            text-align: center;
            margin-top: 20px;
        }
        
        .search-btn {
            background: #0073aa;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .search-btn:hover {
            background: #005a87;
        }
        
        .room-combination {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: white;
        }
        
        .room-type-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #0073aa;
        }
        
        .combo-option {
            margin: 10px 0;
            padding: 10px;
            border-left: 3px solid #0073aa;
            background: #f0f8ff;
        }
        
        .room-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .room-info {
            flex: 1;
        }
        
        .room-actions {
            flex-shrink: 0;
        }
        
        .view-product-btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
        }
        
        .view-product-btn:hover {
            background: #218838;
            color: white;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border: 1px solid #f5c6cb;
            border-radius: 3px;
            margin: 10px 0;
        }
        
        .option-count {
            font-size: 14px;
            font-weight: normal;
            color: #666;
            font-style: italic;
        }
        
        .more-options {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .show-more-btn {
            background: #f8f9fa;
            color: #0073aa;
            border: 1px solid #0073aa;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .show-more-btn:hover {
            background: #0073aa;
            color: white;
        }
        
        .single-combo-option {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .option-separator {
            border: none;
            height: 1px;
            background: #ddd;
            margin: 15px 0;
        }
        
        .room-price {
            color: #28a745;
            font-weight: bold;
            font-size: 16px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Set minimum date to today
            var today = new Date().toISOString().split('T')[0];
            $('#start_date, #end_date').attr('min', today);
            
            // Update minimum end date when start date changes
            $('#start_date').on('change', function() {
                var startDate = new Date($(this).val());
                startDate.setDate(startDate.getDate() + 1);
                var minEndDate = startDate.toISOString().split('T')[0];
                $('#end_date').attr('min', minEndDate);
                
                // If end date is before new minimum, update it
                if ($('#end_date').val() && $('#end_date').val() <= $(this).val()) {
                    $('#end_date').val(minEndDate);
                }
            });
            
            $('#hotel-availability-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'search_hotel_rooms',
                    adults: parseInt($('#adults').val()),
                    kids: parseInt($('#kids').val()),
                    number_of_rooms: $('#number_of_rooms').val(),
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    nonce: '<?php echo wp_create_nonce('hotel_search_nonce'); ?>'
                };
                
                $('#search-loading').show();
                $('#search-results').empty();
                $('.search-btn').prop('disabled', true).text('Searching...');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(response) {
                    $('#search-loading').hide();
                    $('.search-btn').prop('disabled', false).text('Search Available Rooms');
                    
                    if (response.success) {
                        displayResults(response.data);
                    } else {
                        $('#search-results').html('<div class="error-message">' + response.data + '</div>');
                    }
                }).fail(function() {
                    $('#search-loading').hide();
                    $('.search-btn').prop('disabled', false).text('Search Available Rooms');
                    $('#search-results').html('<div class="error-message">Search failed. Please try again.</div>');
                });
            });
            
            function displayResults(data) {
                if (!data.combinations || Object.keys(data.combinations).length === 0) {
                    $('#search-results').html('<div class="no-results">No rooms available for the selected dates and criteria.</div>');
                    return;
                }
                
                var html = '<h3>Available Room Combinations</h3>';
                
                $.each(data.combinations, function(roomType, typeData) {
                    if (!typeData.combo || typeData.combo.length === 0) {
                        return; // Skip if no combinations
                    }
                    
                    var totalOptions = typeData.combo.length;
                    var firstCombo = typeData.combo[0]; // Show only the first option
                    
                    html += '<div class="room-combination">';
                    html += '<div class="room-type-title">' + roomType;
                    
                    // Add option count
                    if (totalOptions > 1) {
                        html += ' <span class="option-count">(' + totalOptions + ' options available)</span>';
                    }
                    
                    html += '</div>';
                    
                    // Show only the first combination
                    html += '<div class="combo-option">';
                    html += '<strong>Option 1' + (totalOptions > 1 ? ' of ' + totalOptions : '') + ':</strong><br>';
                    
                    $.each(firstCombo, function(roomIndex, room) {
                        html += '<div class="room-details">';
                        html += '<div class="room-info">';
                        html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
                        html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
                        
                        // Add price if available
                        if (room.product_price) {
                            html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
                        }
                        
                        html += '</div>';
                        
                        if (room.product_url) {
                            html += '<div class="room-actions">';
                            html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                    });
                    
                    // Add "View more options" link if there are multiple options
                    /*
                    if (totalOptions > 1) {
                        html += '<div class="more-options">';
                        html += '<button class="show-more-btn" data-room-type="' + roomType + '">Show ' + (totalOptions - 1) + ' more option(s)</button>';
                        html += '</div>';
                    }
                        */
                    
                    html += '</div>';
                    html += '</div>';
                });
                
                $('#search-results').html(html);
               /*
                // Handle "Show more options" click
                $('.show-more-btn').on('click', function() {
                    var roomType = $(this).data('room-type');
                    var $button = $(this);
                    var $comboOption = $button.closest('.combo-option');
                    
                    if ($button.hasClass('expanded')) {
                        // Collapse - show only first option
                        showSingleOption(roomType, data.combinations[roomType], $comboOption, $button);
                    } else {
                        // Expand - show all options
                        showAllOptions(roomType, data.combinations[roomType], $comboOption, $button);
                    }
                });*/
            }
            
            function showSingleOption(roomType, typeData, $container, $button) {
                var firstCombo = typeData.combo[0];
                var totalOptions = typeData.combo.length;
                
                var html = '<strong>Option 1' + (totalOptions > 1 ? ' of ' + totalOptions : '') + ':</strong><br>';
                
                $.each(firstCombo, function(roomIndex, room) {
                    html += '<div class="room-details">';
                    html += '<div class="room-info">';
                    html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
                    html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
                    
                    if (room.product_price) {
                        html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
                    }
                    
                    html += '</div>';
                    
                    if (room.product_url) {
                        html += '<div class="room-actions">';
                        html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                        html += '</div>';
                    }
                    
                    html += '</div>';
                });
                
                html += '<div class="more-options">';
                html += '<button class="show-more-btn" data-room-type="' + roomType + '">Show ' + (totalOptions - 1) + ' more option(s)</button>';
                html += '</div>';
                
                $container.html(html);
                $button.removeClass('expanded');
            }
            
            function showAllOptions(roomType, typeData, $container, $button) {
                var html = '';
                
                $.each(typeData.combo, function(index, combo) {
                    html += '<div class="single-combo-option">';
                    html += '<strong>Option ' + (index + 1) + ' of ' + typeData.combo.length + ':</strong><br>';
                    
                    $.each(combo, function(roomIndex, room) {
                        html += '<div class="room-details">';
                        html += '<div class="room-info">';
                        html += '<strong>Room ' + room.nr + '</strong> - ' + room.typeName + '<br>';
                        html += 'Max Adults: ' + room.adultMax + ', Max Children: ' + room.kidMax;
                        
                        if (room.product_price) {
                            html += '<br><span class="room-price">Price: $' + room.product_price + '</span>';
                        }
                        
                        html += '</div>';
                        
                        if (room.product_url) {
                            html += '<div class="room-actions">';
                            html += '<a href="' + room.product_url + '" class="view-product-btn" target="_blank">View Details</a>';
                            html += '</div>';
                        }
                        
                        html += '</div>';
                    });
                    
                    html += '</div>';
                    
                    if (index < typeData.combo.length - 1) {
                        html += '<hr class="option-separator">';
                    }
                });
                
                html += '<div class="more-options">';
                html += '<button class="show-more-btn expanded" data-room-type="' + roomType + '">Show less</button>';
                html += '</div>';
                
                $container.html(html);
                $button.addClass('expanded');
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for room search
     */
    public function ajax_search_rooms() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'hotel_search_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        try {
            $search_params = array(
                'adults' => intval($_POST['adults']),
                'kids' => intval($_POST['kids']),
                'number_of_rooms' => $_POST['number_of_rooms'],
                'start_date' => sanitize_text_field($_POST['start_date']),
                'end_date' => sanitize_text_field($_POST['end_date'])
            );
            
            // Validate dates
            if (strtotime($search_params['start_date']) >= strtotime($search_params['end_date'])) {
                wp_send_json_error('Check-out date must be after check-in date');
            }
            
            if (strtotime($search_params['start_date']) < strtotime('today')) {
                wp_send_json_error('Check-in date cannot be in the past');
            }
            
            // Search for combinations
            $combinations = $this->search_room_combinations($search_params);
            
            if ($combinations === false) {
                wp_send_json_error('Failed to search for room combinations. Please try again.');
            }
            
            // Enhance combinations with product data
            $enhanced_combinations = $this->enhance_combinations_with_products($combinations);
            
            wp_send_json_success(array(
                'combinations' => $enhanced_combinations,
                'search_params' => $search_params
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Search error: ' . $e->getMessage());
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
        
        $response = wp_remote_post($this->api_url, array(
            'timeout' => 30,
            'sslverify' => false,
            'headers' => array(
                'X-API-Secret' => $this->api_secret,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => $json_params
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Hotel search API error: ' . $error_message);
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('Hotel search API response code: ' . $response_code);
        
        if ($response_code !== 200) {
            error_log('Hotel search API returned status: ' . $response_code);
            return false;
        }
        
        $response_body = wp_remote_retrieve_body($response);
        error_log('Hotel search API response body: ' . substr($response_body, 0, 500)); // Log first 500 chars
        
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
                    $product = $this->find_product_by_room_number($room['nr']);
                    
                    if ($product) {
                        $room['product_id'] = $product->ID;
                        $room['product_url'] = get_permalink($product->ID);
                        $room['product_title'] = $product->post_title;
                        $room['product_price'] = get_post_meta($product->ID, '_price', true);
                        
                        // Get product image
                        $image_id = get_post_thumbnail_id($product->ID);
                        if ($image_id) {
                            $room['product_image'] = wp_get_attachment_image_url($image_id, 'medium');
                        }
                    }
                }
            }
        }
        
        return $combinations;
    }
    
    /**
     * Find WooCommerce product by room number
     */
    private function find_product_by_room_number($room_number) {
        $posts = get_posts(array(
            'post_type' => 'product',
            'meta_key' => '_hotel_room_number',
            'meta_value' => $room_number,
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
