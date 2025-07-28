<?php
/**
 * Hotel Room Import Activation
 * 
 * This file should be included in your main plugin file to activate the room importer
 */

// Include the importer class
require_once plugin_dir_path(__FILE__) . 'import/importRooms.php';

// Add admin notice for first-time setup
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id === 'tools_page_hotel-rooms-import') {
        return; // Don't show on the import page itself
    }
    
    if (get_option('hotel_rooms_import_notice_dismissed')) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible" data-notice="hotel-rooms-import">
        <p>
            <strong>Hotel Rooms Import is ready!</strong> 
            <a href="<?php echo admin_url('tools.php?page=hotel-rooms-import'); ?>">Import hotel rooms from your API</a>
        </p>
    </div>
    <script>
    jQuery(document).on('click', '.notice[data-notice="hotel-rooms-import"] .notice-dismiss', function() {
        jQuery.post(ajaxurl, {
            action: 'dismiss_hotel_import_notice',
            nonce: '<?php echo wp_create_nonce('dismiss_notice'); ?>'
        });
    });
    </script>
    <?php
});

// Handle notice dismissal
add_action('wp_ajax_dismiss_hotel_import_notice', function() {
    if (wp_verify_nonce($_POST['nonce'], 'dismiss_notice')) {
        update_option('hotel_rooms_import_notice_dismissed', true);
    }
    wp_die();
});

// Add settings link to plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $import_link = '<a href="' . admin_url('tools.php?page=hotel-rooms-import') . '">Import Rooms</a>';
    array_unshift($links, $import_link);
    return $links;
});
