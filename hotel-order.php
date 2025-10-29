<?php
function dump($data) {
    echo '<pre>' . print_r($data, true) . '</pre>';
}

function dd($data) {
    dump($data);
    die();
}
session_start();

if (!function_exists('masterhotel_get_room_lock_key')) {
    function masterhotel_get_room_lock_key($product_id, $variation_id, $date) {
        $parts = array(
            $product_id ? intval($product_id) : 0,
            $variation_id ? intval($variation_id) : 0,
            $date ? sanitize_text_field($date) : ''
        );

        return 'masterhotel_room_lock_' . md5(implode('|', $parts));
    }
}

if (!function_exists('masterhotel_room_lock_date_has_year')) {
    function masterhotel_room_lock_date_has_year($date_string) {
        return is_string($date_string) && preg_match('/\\b\\d{4}\\b/', $date_string);
    }
}

if (!function_exists('masterhotel_parse_room_lock_date')) {
    function masterhotel_parse_room_lock_date($date_string, $fallback_year = null) {
        if (empty($date_string)) {
            return null;
        }

        $date_string = trim((string) $date_string);
        if ($date_string === '') {
            return null;
        }

        $normalized_input = str_replace(array('\\', '/'), '.', $date_string);
        $formats = array(
            'Y-m-d',
            'd-m-Y',
            'd.m.Y',
            'd-m',
            'd.m'
        );

        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $normalized_input);
            if ($date instanceof DateTime) {
                if ($fallback_year && strpos($format, 'Y') === false) {
                    $date->setDate(intval($fallback_year), intval($date->format('m')), intval($date->format('d')));
                }

                return $date;
            }
        }

        try {
            return new DateTime($date_string);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('masterhotel_get_room_lock_dates')) {
    function masterhotel_get_room_lock_dates($start_date, $end_date) {
        if (empty($start_date)) {
            return array();
        }

        $start = masterhotel_parse_room_lock_date($start_date);
        if (!$start) {
            return array();
        }

        $fallback_year = intval($start->format('Y'));
        $end = masterhotel_parse_room_lock_date($end_date, $fallback_year);

        if ($end_date && $end && $end <= $start && !masterhotel_room_lock_date_has_year($end_date)) {
            $end->modify('+1 year');
        }

        if (!$end || $end <= $start) {
            $end = clone $start;
            $end->modify('+1 day');
        }

        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        $dates = array();
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }
}
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

    $session_id = session_id();
    if (!$session_id) {
        session_start();
        $session_id = session_id();
    }

    $lock_keys = array();

    $lock_dates = masterhotel_get_room_lock_dates($booking_meta['start_date'], $booking_meta['end_date']);

    if (!empty($lock_dates)) {
        foreach ($items as $item) {
            $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
            $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;

            if (!$product_id) {
                continue;
            }

            foreach ($lock_dates as $lock_date) {
                $lock_key = masterhotel_get_room_lock_key($product_id, $variation_id, $lock_date);
                if (!$lock_key) {
                    continue;
                }

                $existing_lock = get_transient($lock_key);
                if ($existing_lock && $existing_lock !== $session_id) {
                    wp_send_json_error(array(
                        'message' => __('Camera selectată nu mai este disponibilă pentru perioada aleasă. Vă rugăm să alegeți altă cameră sau să încercați din nou în câteva minute.', 'master-hotel')
                    ));
                }

                $lock_keys[] = $lock_key;
            }
        }
    }

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
    foreach ($items as $item) {
        $product_id = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $variation_id = isset($item['variation_id']) ? intval($item['variation_id']) : 0;
        $quantity = $nights;
        if ($product_id) {
            // Add a unique key to force separate cart lines
            $unique_key = uniqid('line_', true);
            $custom_cart_item_data = array_merge(array('masterhotel_unique_key' => $unique_key), $booking_meta);
            if ($variation_id) {
                WC()->cart->add_to_cart($product_id, $quantity, $variation_id, array(), $custom_cart_item_data);
            } else {
                WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $custom_cart_item_data);
            }
            $added++;
        }
    }

    if (!empty($lock_keys)) {
        $lock_keys = array_unique($lock_keys);
        $lock_duration = defined('MINUTE_IN_SECONDS') ? 15 * MINUTE_IN_SECONDS : 15 * 60;
        foreach ($lock_keys as $key) {
            set_transient($key, $session_id, $lock_duration);
        }
    }
    wp_send_json_success(array(
        'added' => $added,
        'cart_url' => function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : ''
    ));
}

add_action('wp_ajax_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');
add_action('wp_ajax_nopriv_masterhotel_add_multiple_to_cart', 'masterhotel_add_multiple_to_cart');

function redirect_cart_to_checkout() {
    if ( function_exists('is_cart') && is_cart() ) {
        if ( function_exists('wc_get_checkout_url') ) {
            wp_redirect( wc_get_checkout_url() );
            exit;
        }
    }
}
add_action( 'template_redirect', 'redirect_cart_to_checkout' );

