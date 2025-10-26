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
        add_action('woocommerce_checkout_process', array($this, 'validate_custom_checkout_fields'));
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
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-wide', 'company-field'),
            'clear'       => true,
            'priority'    => 26
        );

        $fields['billing']['billing_cui'] = array(
            'label'       => __('CUI', 'woocommerce'),
            'placeholder' => _x('CUI', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 27
        );

        $fields['billing']['billing_reg_com'] = array(
            'label'       => __('Reg.Com.', 'woocommerce'),
            'placeholder' => _x('Reg.Com.', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 28
        );

        $fields['billing']['billing_banca'] = array(
            'label'       => __('Banca', 'woocommerce'),
            'placeholder' => _x('Banca', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 29
        );

        $fields['billing']['billing_cont_iban'] = array(
            'label'       => __('Cont IBAN', 'woocommerce'),
            'placeholder' => _x('Cont IBAN', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 30
        );

        $fields['billing']['billing_company_city'] = array(
            'label'       => __('Oraș Companie', 'woocommerce'),
            'placeholder' => _x('Orașul companiei', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 31
        );

        $fields['billing']['billing_company_state'] = array(
            'label'       => __('Județ/Stat Companie', 'woocommerce'),
            'placeholder' => _x('Județul sau statul companiei', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 32
        );

        $fields['billing']['billing_company_country'] = array(
            'label'       => __('Țară Companie', 'woocommerce'),
            'placeholder' => _x('Țara companiei', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-wide', 'company-field'),
            'clear'       => true,
            'priority'    => 33
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
            echo '<p><strong>' . __('Oraș Companie', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_company_city', true) . '</p>';
            echo '<p><strong>' . __('Județ/Stat Companie', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_company_state', true) . '</p>';
            echo '<p><strong>' . __('Țară Companie', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_company_country', true) . '</p>';
        }
    }

    public function save_custom_checkout_fields($order_id) {
        $fields = array(
            'billing_company_details',
            'billing_company_name',
            'billing_cui',
            'billing_reg_com',
            'billing_banca',
            'billing_cont_iban',
            'billing_company_city',
            'billing_company_state',
            'billing_company_country'
        );

        foreach ($fields as $field) {
            if (!empty($_POST[$field])) {
                update_post_meta($order_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function validate_custom_checkout_fields() {
        // Check if company details checkbox is checked
        if (isset($_POST['billing_company_details']) && $_POST['billing_company_details'] == '1') {
            // Define required company fields
            $required_fields = array(
                'billing_company_name' => __('Nume Companie', 'woocommerce'),
                'billing_cui' => __('CUI', 'woocommerce'),
                'billing_reg_com' => __('Reg.Com.', 'woocommerce'),
                'billing_banca' => __('Banca', 'woocommerce'),
                'billing_cont_iban' => __('Cont IBAN', 'woocommerce'),
                'billing_company_city' => __('Oraș Companie', 'woocommerce'),
                'billing_company_state' => __('Județ/Stat Companie', 'woocommerce'),
                'billing_company_country' => __('Țară Companie', 'woocommerce')
            );

            // Validate each required field
            foreach ($required_fields as $field => $label) {
                if (empty($_POST[$field])) {
                    wc_add_notice(sprintf(__('%s este obligatoriu când solicitați factură pe firmă.', 'woocommerce'), '<strong>' . $label . '</strong>'), 'error');
                }
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

                function toggleCompanyFields() {
                    if ($('#billing_company_details').is(':checked')) {
                        $('.company-field').closest('.form-row').show();
                        // Add required attribute and asterisk to company fields
                        $('.company-field').each(function() {
                            $(this).attr('required', 'required');
                            var $label = $(this).closest('.form-row').find('label');
                            if (!$label.find('.required').length) {
                                $label.append(' <abbr class="required" title="required">*</abbr>');
                            }
                        });
                    } else {
                        $('.company-field').closest('.form-row').hide();
                        // Remove required attribute and asterisk from company fields
                        $('.company-field').each(function() {
                            $(this).removeAttr('required');
                            $(this).closest('.form-row').find('label .required').remove();
                        });
                    }
                }

                // Toggle company fields when checkbox is clicked
                $('#billing_company_details').change(function() {
                    toggleCompanyFields();
                });

                // Check initial state
                toggleCompanyFields();

                // Additional validation on form submission
                $('body').on('checkout_error', function() {
                    // Clear previous error styling
                    $('.company-field').closest('.form-row').removeClass('woocommerce-invalid');
                });

                // Validate before WooCommerce processes the form
                $(document.body).on('checkout_place_order', function() {
                    if ($('#billing_company_details').is(':checked')) {
                        var hasErrors = false;
                        var errorMessage = '';
                        
                        $('.company-field').each(function() {
                            var $field = $(this);
                            var $row = $field.closest('.form-row');
                            var fieldLabel = $row.find('label').text().replace('*', '').trim();
                            
                            if ($field.val().trim() === '') {
                                hasErrors = true;
                                $row.addClass('woocommerce-invalid woocommerce-invalid-required-field');
                                if (errorMessage === '') {
                                    errorMessage = fieldLabel + ' este obligatoriu când solicitați factură pe firmă.';
                                }
                            } else {
                                $row.removeClass('woocommerce-invalid woocommerce-invalid-required-field');
                            }
                        });
                        
                        if (hasErrors) {
                            // Scroll to first error
                            $('html, body').animate({
                                scrollTop: $('.woocommerce-invalid').first().offset().top - 100
                            }, 500);
                        }
                        
                        return !hasErrors;
                    }
                    return true;
                });

                // Real-time validation
                $('.company-field').on('blur change', function() {
                    var $field = $(this);
                    var $row = $field.closest('.form-row');
                    
                    if ($('#billing_company_details').is(':checked')) {
                        if ($field.val().trim() === '') {
                            $row.addClass('woocommerce-invalid');
                        } else {
                            $row.removeClass('woocommerce-invalid');
                        }
                    }
                });
            });
        </script>
        <style>
            .woocommerce-invalid {
                border-color: #e2401c !important;
            }
            .woocommerce-invalid input,
            .woocommerce-invalid select {
                border-color: #e2401c !important;
                box-shadow: 0 0 5px rgba(226, 64, 28, 0.3) !important;
            }
            .woocommerce-invalid-required-field {
                background-color: rgba(226, 64, 28, 0.05) !important;
            }
            .company-field:focus {
                outline: none;
                border-color: #0073aa !important;
                box-shadow: 0 0 5px rgba(0, 115, 170, 0.3) !important;
            }
        </style>
        <?php
    }
}
