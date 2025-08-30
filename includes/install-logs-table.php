<?php
// Install or update the custom logs table for MasterHotelLogHelper
function masterhotel_install_logs_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'masterhotel_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        log_time DATETIME NOT NULL,
        log_level VARCHAR(20) NOT NULL,
        log_message TEXT NOT NULL,
        log_context TEXT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'masterhotel_install_logs_table');
