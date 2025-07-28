<?php
/*
Plugin Name: Hotel Room Search
Description: A hotel room search engine with a shortcode and jQuery AJAX.
Version: 1.0
Author: Your Name
*/

// Register shortcode
add_shortcode('hotel_room_search', 'hrs_render_search_form');

function hrs_render_search_form() {
    ob_start();
    // Detect search params in URL
    $is_search = isset($_GET['checkin'], $_GET['checkout'], $_GET['adults'], $_GET['children'], $_GET['rooms']);
    ?>
    <form id="hotel-room-search-form" action="?" method="get">
        <label>Check-in date: <input type="date" name="checkin" required value="<?php echo isset($_GET['checkin']) ? esc_attr($_GET['checkin']) : ''; ?>"></label><br>
        <label>Check-out date: <input type="date" name="checkout" required value="<?php echo isset($_GET['checkout']) ? esc_attr($_GET['checkout']) : ''; ?>"></label><br>
        <label>Adults: <input type="number" name="adults" min="1" value="<?php echo isset($_GET['adults']) ? esc_attr($_GET['adults']) : '1'; ?>" required></label><br>
        <label>Children: <input type="number" name="children" min="0" value="<?php echo isset($_GET['children']) ? esc_attr($_GET['children']) : '0'; ?>" required></label><br>
        <label>Rooms: <input type="number" name="rooms" min="1" value="<?php echo isset($_GET['rooms']) ? esc_attr($_GET['rooms']) : '1'; ?>" required></label><br>
        <button type="submit">Search</button>
    </form>
    <div id="hotel-room-search-results">
    <?php if ($is_search): ?>
        <script>window.hrs_search_params = <?php echo json_encode($_GET); ?>;</script>
        <style>
        .hrs-table-wrapper { overflow-x: auto; }
        .hrs-table {
            display: table;
            width: 100%;
            border-collapse: collapse;
            min-width: 400px;
        }
        .hrs-table-header, .hrs-table-row {
            display: table-row;
        }
        .hrs-table-header > div, .hrs-table-row > div {
            display: table-cell;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        .hrs-table-header > div {
            font-weight: bold;
            background: #f7f7f7;
        }
        .hrs-room-img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        @media (max-width: 600px) {
            .hrs-table, .hrs-table-header, .hrs-table-row, .hrs-table-header > div, .hrs-table-row > div {
                display: block;
                width: 100%;
            }
            .hrs-table-header { display: none; }
            .hrs-table-row { margin-bottom: 16px; border-bottom: 2px solid #eee; }
        }
        </style>
    <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Enqueue scripts
add_action('wp_enqueue_scripts', 'hrs_enqueue_scripts');
function hrs_enqueue_scripts() {
    if (!is_singular()) return;
    global $post;
    if (has_shortcode($post->post_content, 'hotel_room_search')) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('hrs-search', plugins_url('hrs-search.js', __FILE__), array('jquery'), null, true);
        wp_localize_script('hrs-search', 'hrs_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        // Adaugă Bootstrap CSS
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    }
}

// AJAX handler
add_action('wp_ajax_hrs_search_rooms', 'hrs_search_rooms');
add_action('wp_ajax_nopriv_hrs_search_rooms', 'hrs_search_rooms');
function hrs_search_rooms() {
    // For now, return dummy data
    $rooms = array(
        array('name' => 'Deluxe Room', 'price' => 120),
        array('name' => 'Suite', 'price' => 200),
        array('name' => 'Standard Room', 'price' => 80),
    );
    wp_send_json_success($rooms);
}

// Include the hotel room importer
require_once plugin_dir_path(__FILE__) . 'activate-importer.php';

// Include the API test tool
require_once plugin_dir_path(__FILE__) . 'test-api.php';

// Include the room availability search
require_once plugin_dir_path(__FILE__) . 'room-search.php';

// Debug: Log that all files are loaded
error_log('MasterHotel plugin files loaded successfully');
