<?php
function dump($data) {
    echo '<pre>' . print_r($data, true) . '</pre>';
}

function dd($data) {
    dump($data);
    die();
}
session_start();
// Add custom meta when order is created (checkout)
add_action('woocommerce_new_order', 'add_custom_order_meta_on_create', 20, 1);
function add_custom_order_meta_on_create($order_id) {
        add_post_meta($order_id, '_order_info',  json_encode($_SESSION['hotel_search_params']));
}


// Display '_order_info' in WooCommerce admin order details as a formatted table in Romanian
add_action('woocommerce_admin_order_data_after_order_details', function($order){
    $value = get_post_meta($order->get_id(), '_order_info', true);
    $data = json_decode($value, true);
    $labels = array(
        'adults' => 'Adulți',
        'kids' => 'Copii',
        'number_of_rooms' => 'Număr camere',
        'start_date' => 'Data sosirii',
        'end_date' => 'Data plecării',
    );
    echo '<div class="hotel-order-fields"><h4>Detalii rezervare</h4>';
    if (is_array($data)) {
        echo '<table style="border-collapse:collapse;">';
        foreach ($data as $key => $val) {
            $label = isset($labels[$key]) ? $labels[$key] : $key;
            echo '<tr><td style="padding:2px 8px;"><strong>' . esc_html($label) . '</strong></td><td style="padding:2px 8px;">' . esc_html($val) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<em>Date indisponibile</em>';
    }
    echo '</div>';
});


/**
 * Add multiple products (and variations) to WooCommerce cart via AJAX.
 * Accepts POST param 'items' as JSON array: [{product_id, variation_id, quantity}]
 */
function masterhotel_add_multiple_to_cart() {
    if (!class_exists('WC_Cart')) {
        wp_send_json_error('WooCommerce not loaded');
    }
    $items = isset($_POST['items']) ? json_decode(stripslashes($_POST['items']), true) : array();
    if (!is_array($items) || empty($items)) {
        wp_send_json_error('No items provided');
    }
    // Empty the cart before adding new items
    if (WC()->cart) {
        WC()->cart->empty_cart();
    }
    $added = 0;
   
    // Get booking meta from POST (sent from search form)
    $booking_meta = array(
        'adults' => isset($_POST['adults']) ? intval($_POST['adults']) : '',
        'kids' => isset($_POST['kids']) ? intval($_POST['kids']) : '',
        'number_of_rooms' => isset($_POST['number_of_rooms']) ? sanitize_text_field($_POST['number_of_rooms']) : '',
        'start_date' => isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '',
        'end_date' => isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '',
    );

    foreach ($items as $item) {
        $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
        if ($product_id) {
            if ($variation_id) {
                WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $booking_meta);
            } else {
                WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $booking_meta);
            }
            $added++;
        }
    }
    wp_send_json_success(array(
        'added' => $added,
        'cart_url' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : ''
    ));
}

add_action('wp_ajax_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');
add_action('wp_ajax_nopriv_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');

