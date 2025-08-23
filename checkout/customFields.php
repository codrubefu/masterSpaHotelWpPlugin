<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'initialize_custom_checkout_fields');

function initialize_custom_checkout_fields() {
    new CustomCheckoutFields();
}

class CustomCheckoutFields {
    public function __construct() {
        add_filter('woocommerce_checkout_fields', array($this, 'add_custom_checkout_fields'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_custom_fields_in_admin_order'), 10, 1);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_custom_checkout_fields'));
    }

    public function add_custom_checkout_fields($fields) {
        $fields['billing']['billing_company_details'] = array(
            'type'        => 'checkbox',
            'label'       => __('Doresc factură pe firmă', 'woocommerce'),
            'class'       => array('form-row-wide'),
            'priority'    => 25,
            'clear'       => true,
            'required'    => false
        );

        $fields['billing']['billing_company_name'] = array(
            'label'       => __('Nume Companie', 'woocommerce'),
            'placeholder' => _x('Nume Companie', 'placeholder', 'woocommerce'),
            'required'    => false,
            'class'       => array('form-row-wide', 'company-field'),
            'clear'       => true,
            'priority'    => 26
        );

        $fields['billing']['billing_cui'] = array(
            'label'       => __('CUI', 'woocommerce'),
            'placeholder' => _x('CUI', 'placeholder', 'woocommerce'),
            'required'    => false,
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 27
        );

        $fields['billing']['billing_reg_com'] = array(
            'label'       => __('Reg.Com.', 'woocommerce'),
            'placeholder' => _x('Reg.Com.', 'placeholder', 'woocommerce'),
            'required'    => false,
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 28
        );

        $fields['billing']['billing_banca'] = array(
            'label'       => __('Banca', 'woocommerce'),
            'placeholder' => _x('Banca', 'placeholder', 'woocommerce'),
            'required'    => false,
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 29
        );

        $fields['billing']['billing_cont_iban'] = array(
            'label'       => __('Cont IBAN', 'woocommerce'),
            'placeholder' => _x('Cont IBAN', 'placeholder', 'woocommerce'),
            'required'    => false,
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 30
        );

        return $fields;
    }

    public function display_custom_fields_in_admin_order($order) {
        $company_details = get_post_meta($order->get_id(), '_billing_company_details', true);
        if ($company_details) {
            echo '<p><strong>' . __('Factură pe firmă', 'woocommerce') . ':</strong> Da</p>';
            echo '<p><strong>' . __('Nume Companie', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_company_name', true) . '</p>';
            echo '<p><strong>' . __('CUI', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_cui', true) . '</p>';
            echo '<p><strong>' . __('Reg.Com.', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_reg_com', true) . '</p>';
            echo '<p><strong>' . __('Banca', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_banca', true) . '</p>';
            echo '<p><strong>' . __('Cont IBAN', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_cont_iban', true) . '</p>';
        }
    }

    public function save_custom_checkout_fields($order_id) {
        $fields = array(
            'billing_company_details',
            'billing_company_name',
            'billing_cui',
            'billing_reg_com',
            'billing_banca',
            'billing_cont_iban'
        );

        foreach ($fields as $field) {
            if (!empty($_POST[$field])) {
                update_post_meta($order_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}

// Add jQuery to toggle company fields visibility
add_action('wp_footer', 'company_fields_script');
function company_fields_script() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Hide company fields initially
                $('.company-field').closest('.form-row').hide();

                // Toggle company fields when checkbox is clicked
                $('#billing_company_details').change(function() {
                    if ($(this).is(':checked')) {
                        $('.company-field').closest('.form-row').show();
                    } else {
                        $('.company-field').closest('.form-row').hide();
                    }
                });

                // Check initial state
                if ($('#billing_company_details').is(':checked')) {
                    $('.company-field').closest('.form-row').show();
                }
            });
        </script>
        <?php
    }
}
