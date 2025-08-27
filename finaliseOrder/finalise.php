<?php
// Send order data to webhook when order is completed
add_action('woocommerce_order_status_processing', function($order_id) {

    if (!class_exists('WC_Order')) return;
    $order = wc_get_order($order_id);
    if (!$order) return;

	// Get webhook URL from config
	if (!class_exists('MasterHotelConfig')) {
     
		if (file_exists(dirname(__FILE__,2).'/config.php')) {
			require_once dirname(__FILE__,2).'/config.php';
		}
	}
	if (!class_exists('MasterHotelConfig')) return;
        
	$webhook_url = MasterHotelConfig::get_config('order_completed_webhook_url');

	if (empty($webhook_url)) return;

	// Collect order data
	$order_data = $order->get_data();
	$order_data['items'] = array();
	foreach ($order->get_items() as $item) {
		$item_data = $item->get_data();
		$product_id = $item_data['product_id'];
		$product_meta = array();
		if ($product_id) {
			$product = wc_get_product($product_id);
			if ($product) {
				// Get all meta for the product
				$meta = get_post_meta($product_id);
				$product_meta = $meta;
			}
		}
		$item_data['product_meta_input'] = $product_meta;
		$order_data['items'][] = $item_data;
	}
	$order_data['meta'] = array();
	foreach ($order->get_meta_data() as $meta) {
		$order_data['meta'][$meta->key] = $meta->value;
	}
	// Add custom meta if exists
	$custom_info = get_post_meta($order_id, '_order_info', true);
	if ($custom_info) {
		$order_data['custom_info'] = json_decode($custom_info, true);
	}

	// Use helper for cURL
	if (!class_exists('MasterHotelCurlHelper')) {
		$helper_path = dirname(__FILE__,2).'/includes/MasterHotelCurlHelper.php';
		if (file_exists($helper_path)) {
			require_once $helper_path;
		}
	}
	if (class_exists('MasterHotelCurlHelper')) {
		MasterHotelCurlHelper::post_json($webhook_url, $order_data);
	}
});
