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

/**
 * Aggregate cart request items so that all variations of the same parent
 * product are merged into a single parent product line.
 *
 * Business rule:
 * - each distinct product is charged exactly 200 RON, regardless of quantity
 * - quantity in cart is forced to 1 for each distinct product
 *
 * @param array $items
 * @return array
 */
function masterhotel_aggregate_items_by_parent_product($items) {
    $fixed_unit_price = 200.0;
    $grouped = array();

    foreach ($items as $item) {
        $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
        if (!$product_id) {
            continue;
        }

        $parent_product_id = $product_id;
        $room_slot = isset($item['room_slot']) ? intval($item['room_slot']) : -1;

        if ($variation_id) {
            $variation_product = wc_get_product($variation_id);
            if (!$variation_product) {
                continue;
            }

            $parent_product_id = $variation_product->get_parent_id() ? intval($variation_product->get_parent_id()) : $product_id;
        } else {
            $product = wc_get_product($product_id);
            if (!$product) {
                continue;
            }
        }

        $group_key = $parent_product_id . ':' . $room_slot;

        if (!isset($grouped[$group_key])) {
            $grouped[$group_key] = array(
                'product_id' => $parent_product_id,
                'quantity' => 1,
                'weighted_unit_price' => $fixed_unit_price,
                'original_total_price' => 0.0,
                'original_total_nights' => 0,
            );
        }

        $original_unit_price = isset($item['original_unit_price']) ? (float) $item['original_unit_price'] : 0.0;
        $original_quantity = isset($item['quantity']) ? max(1, intval($item['quantity'])) : 1;
        if ($original_unit_price > 0) {
            $grouped[$group_key]['original_total_price'] += ($original_unit_price * $original_quantity);
            $grouped[$group_key]['original_total_nights'] += $original_quantity;
        }
    }

    $aggregated_items = array();
    foreach ($grouped as $group) {
        $original_unit_price = 0.0;
        if (!empty($group['original_total_nights'])) {
            $original_unit_price = $group['original_total_price'] / $group['original_total_nights'];
        }

        $aggregated_items[] = array(
            'product_id' => $group['product_id'],
            'quantity' => $group['quantity'],
            'weighted_unit_price' => $group['weighted_unit_price'],
            'original_unit_price' => $original_unit_price,
            'original_total_price' => $group['original_total_price'],
            'original_total_nights' => $group['original_total_nights'],
        );
    }

    return $aggregated_items;
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

    // Calculate nights (quantity) from start_date and end_date
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $nights = 1;
    if ($start_date && $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $nights = max(1, (int)$interval->format('%a'));
    }
    $items_with_default_quantity = array();
    foreach ($items as $item) {
        $item_quantity = isset($item['quantity']) ? max(1, intval($item['quantity'])) : $nights;
        $items_with_default_quantity[] = array(
            'product_id' => isset($item['product_id']) ? intval($item['product_id']) : 0,
            'variation_id' => isset($item['variation_id']) ? intval($item['variation_id']) : 0,
            'quantity' => $item_quantity,
            'room_slot' => isset($item['room_slot']) ? intval($item['room_slot']) : -1,
            'original_unit_price' => isset($item['original_unit_price']) ? (float) $item['original_unit_price'] : 0.0,
        );
    }

    $aggregated_items = masterhotel_aggregate_items_by_parent_product($items_with_default_quantity);

    foreach ($aggregated_items as $item) {
        $product_id = $item['product_id'];
        $quantity = $item['quantity'];
        $weighted_unit_price = $item['weighted_unit_price'];
        $original_unit_price = isset($item['original_unit_price']) ? (float) $item['original_unit_price'] : 0.0;
        $original_total_price = isset($item['original_total_price']) ? (float) $item['original_total_price'] : 0.0;
        $original_total_nights = isset($item['original_total_nights']) ? intval($item['original_total_nights']) : 0;

        // Add a unique key to force separate cart lines
        $unique_key = uniqid('line_', true);
        $custom_cart_item_data = array_merge(
            array(
                'masterhotel_unique_key' => $unique_key,
                'masterhotel_custom_unit_price' => $weighted_unit_price,
                'masterhotel_original_unit_price' => $original_unit_price,
                'masterhotel_original_total_price' => $original_total_price,
                'masterhotel_original_total_nights' => $original_total_nights,
            ),
            $booking_meta
        );

        WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $custom_cart_item_data);
        $added++;
    }
    wp_send_json_success(array(
        'added' => $added,
        'cart_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : ''
    ));
}

add_action('wp_ajax_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');
add_action('wp_ajax_nopriv_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');

/**
 * Apply custom weighted unit prices (saved in cart item meta) to cart items.
 */
function masterhotel_apply_custom_weighted_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!$cart || !method_exists($cart, 'get_cart')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (
            isset($cart_item['masterhotel_custom_unit_price']) &&
            isset($cart_item['data']) &&
            is_object($cart_item['data'])
        ) {
            $custom_price = (float)$cart_item['masterhotel_custom_unit_price'];
            if ($custom_price > 0) {
                $cart_item['data']->set_price($custom_price);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'masterhotel_apply_custom_weighted_price', 20, 1);

function redirect_cart_to_checkout() {
    if ( function_exists('is_cart') && is_cart() ) {
        if ( function_exists('WC') && WC()->cart ) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product_id = $cart_item['product_id'];
                if (has_term('room', 'product_tag', $product_id)) {
                    if ( function_exists('wc_get_checkout_url') ) {
                        wp_redirect( wc_get_checkout_url() );
                        exit;
                    }
                }
            }
        }
    }
}
add_action( 'template_redirect', 'redirect_cart_to_checkout' );

function masterhotel_redirect_room_search_query() {
    $search_params = array(
        'room-quantity',
        'adult-quantity',
        'child-quantity',
        'search_rooms',
        'checkin',
        'checkout',
    );

    $has_search_param = false;

    foreach ( $search_params as $search_param ) {
        if ( array_key_exists( $search_param, $_GET ) ) {
            $has_search_param = true;
            break;
        }
    }

    if ( ! $has_search_param || is_admin() || wp_doing_ajax() || is_page( 'rezerva-o-camera' ) ) {
        return;
    }

    $redirect_query = array(
        'start_date'      => isset( $_GET['checkin'] ) ? sanitize_text_field( wp_unslash( $_GET['checkin'] ) ) : '',
        'end_date'        => isset( $_GET['checkout'] ) ? sanitize_text_field( wp_unslash( $_GET['checkout'] ) ) : '',
        'adults'          => isset( $_GET['adult-quantity'] ) ? absint( wp_unslash( $_GET['adult-quantity'] ) ) : 0,
        'kids'            => isset( $_GET['child-quantity'] ) ? absint( wp_unslash( $_GET['child-quantity'] ) ) : 0,
        'number_of_rooms' => isset( $_GET['room-quantity'] ) ? absint( wp_unslash( $_GET['room-quantity'] ) ) : 0,
    );

    $target_url = add_query_arg( $redirect_query, home_url( '/rezerva-o-camera/' ) );

    wp_safe_redirect( $target_url );
    exit;
}
add_action( 'template_redirect', 'masterhotel_redirect_room_search_query' );


/**
 * Persist original (pre-fixed) pricing data from cart item to order item meta
 * so it can be sent to external API/webhook.
 */
function masterhotel_add_original_price_meta_to_order_item($item, $cart_item_key, $values, $order) {
    $meta_keys = array(
        'masterhotel_original_unit_price',
        'masterhotel_original_total_price',
        'masterhotel_original_total_nights',
    );

    foreach ($meta_keys as $meta_key) {
        if (isset($values[$meta_key])) {
            $item->add_meta_data($meta_key, $values[$meta_key], true);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'masterhotel_add_original_price_meta_to_order_item', 10, 4);
