<?php


// Add meta box under Product Image
function mshwp_add_related_article_metabox() {
    add_meta_box(
        'mshwp_related_article',
        __('Related Article', 'masterSpaHotelWpPlugin'),
        'mshwp_related_article_metabox_content',
        'product',
        'side',
        'low'
    );
}
add_action('add_meta_boxes', 'mshwp_add_related_article_metabox');

function mshwp_related_article_metabox_content($post) {
    $selected = get_post_meta($post->ID, '_related_article_id', true);
    $args = array(
        'post_type' => 'loftocean_room',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'pending', 'draft', 'private', 'future'),
        'orderby' => 'title',
        'order' => 'ASC',
    );
    $posts = get_posts($args);
    echo '<label for="_related_article_id">' . __('Select a related article:', 'masterSpaHotelWpPlugin') . '</label>';
    echo '<select id="_related_article_id" name="_related_article_id" style="width:100%;margin-top:4px;">';
    echo '<option value="">' . __('None', 'masterSpaHotelWpPlugin') . '</option>';
    foreach ($posts as $article) {
        $is_selected = ($selected == $article->ID) ? 'selected' : '';
        $status = ($article->post_status !== 'publish') ? ' (' . esc_html($article->post_status) . ')' : '';
        echo '<option value="' . esc_attr($article->ID) . '" ' . $is_selected . '>' . esc_html($article->post_title . $status) . '</option>';
    }
    echo '</select>';
}


// Save meta box value
add_action('save_post_product', function($post_id) {
    if (isset($_POST['_related_article_id'])) {
        update_post_meta($post_id, '_related_article_id', intval($_POST['_related_article_id']));
    }
});

// (Optional) Display related article on product page
add_action('woocommerce_single_product_summary', function() {
    global $post;
    $related_id = get_post_meta($post->ID, '_related_article_id', true);
    if ($related_id) {
        $article = get_post($related_id);
        if ($article && $article->post_status === 'publish') {
            echo '<div class="related-article"><strong>Related Article:</strong> <a href="' . get_permalink($article) . '" target="_blank">' . esc_html($article->post_title) . '</a></div>';
        }
    }
}, 25);
