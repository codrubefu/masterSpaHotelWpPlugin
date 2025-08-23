<?php
/**
 * Plugin Name: MasterSpa Hotel WordPress Plugin
 * Description: Hotel room availability search and management system with WooCommerce integration
 * Version: 2.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MASTER_HOTEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MASTER_HOTEL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Debug: Log that main plugin file is loaded
error_log('MasterSpa Hotel plugin loaded');

// Include required files
require_once MASTER_HOTEL_PLUGIN_DIR . 'config.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'import/importRooms.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'room-search.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'activate-importer.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'hotel-room-search.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'hotel-order.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'finaliseOrder/finalise.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'test-api.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'related-article-for-product.php';
require_once MASTER_HOTEL_PLUGIN_DIR . 'checkout/customFields.php';
// Debug: Log that all files are included
error_log('All MasterSpa Hotel plugin files included successfully');

// Debug: Log that all files are loaded
error_log('MasterHotel plugin files loaded successfully');
