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

        $fields['billing']['billing_company_address'] = array(
            'label'       => __('Adresa Companie', 'woocommerce'),
            'placeholder' => _x('Adresa companiei', 'placeholder', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 32
        );

        $fields['billing']['billing_company_state'] = array(
            'type'        => 'select',
            'label'       => __('Județ/Stat Companie', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-first', 'company-field'),
            'priority'    => 33,
            'options'     => array(
                '' => __('Selectați județul/statul...', 'woocommerce')
            )
        );

        $fields['billing']['billing_company_country'] = array(
            'type'        => 'select',
            'label'       => __('Țară Companie', 'woocommerce'),
            'required'    => false, // Will be dynamically required via JavaScript
            'class'       => array('form-row-last', 'company-field'),
            'clear'       => true,
            'priority'    => 34,
            'options'     => array(
                ''   => __('Selectați țara...', 'woocommerce'),
                'RO' => __('România', 'woocommerce'),
                'AD' => __('Andorra', 'woocommerce'),
                'AE' => __('Emiratele Arabe Unite', 'woocommerce'),
                'AF' => __('Afganistan', 'woocommerce'),
                'AG' => __('Antigua și Barbuda', 'woocommerce'),
                'AI' => __('Anguilla', 'woocommerce'),
                'AL' => __('Albania', 'woocommerce'),
                'AM' => __('Armenia', 'woocommerce'),
                'AO' => __('Angola', 'woocommerce'),
                'AQ' => __('Antarctica', 'woocommerce'),
                'AR' => __('Argentina', 'woocommerce'),
                'AS' => __('Samoa Americană', 'woocommerce'),
                'AT' => __('Austria', 'woocommerce'),
                'AU' => __('Australia', 'woocommerce'),
                'AW' => __('Aruba', 'woocommerce'),
                'AX' => __('Insulele Åland', 'woocommerce'),
                'AZ' => __('Azerbaidjan', 'woocommerce'),
                'BA' => __('Bosnia și Herțegovina', 'woocommerce'),
                'BB' => __('Barbados', 'woocommerce'),
                'BD' => __('Bangladesh', 'woocommerce'),
                'BE' => __('Belgia', 'woocommerce'),
                'BF' => __('Burkina Faso', 'woocommerce'),
                'BG' => __('Bulgaria', 'woocommerce'),
                'BH' => __('Bahrain', 'woocommerce'),
                'BI' => __('Burundi', 'woocommerce'),
                'BJ' => __('Benin', 'woocommerce'),
                'BL' => __('Saint Barthélemy', 'woocommerce'),
                'BM' => __('Bermuda', 'woocommerce'),
                'BN' => __('Brunei', 'woocommerce'),
                'BO' => __('Bolivia', 'woocommerce'),
                'BQ' => __('Bonaire, Sint Eustatius și Saba', 'woocommerce'),
                'BR' => __('Brazilia', 'woocommerce'),
                'BS' => __('Bahamas', 'woocommerce'),
                'BT' => __('Bhutan', 'woocommerce'),
                'BV' => __('Insula Bouvet', 'woocommerce'),
                'BW' => __('Botswana', 'woocommerce'),
                'BY' => __('Belarus', 'woocommerce'),
                'BZ' => __('Belize', 'woocommerce'),
                'CA' => __('Canada', 'woocommerce'),
                'CC' => __('Insulele Cocos (Keeling)', 'woocommerce'),
                'CD' => __('Republica Democrată Congo', 'woocommerce'),
                'CF' => __('Republica Centrafricană', 'woocommerce'),
                'CG' => __('Congo', 'woocommerce'),
                'CH' => __('Elveția', 'woocommerce'),
                'CI' => __('Coasta de Fildeș', 'woocommerce'),
                'CK' => __('Insulele Cook', 'woocommerce'),
                'CL' => __('Chile', 'woocommerce'),
                'CM' => __('Camerun', 'woocommerce'),
                'CN' => __('China', 'woocommerce'),
                'CO' => __('Columbia', 'woocommerce'),
                'CR' => __('Costa Rica', 'woocommerce'),
                'CU' => __('Cuba', 'woocommerce'),
                'CV' => __('Capul Verde', 'woocommerce'),
                'CW' => __('Curaçao', 'woocommerce'),
                'CX' => __('Insula Christmas', 'woocommerce'),
                'CY' => __('Cipru', 'woocommerce'),
                'CZ' => __('Republica Cehă', 'woocommerce'),
                'DE' => __('Germania', 'woocommerce'),
                'DJ' => __('Djibouti', 'woocommerce'),
                'DK' => __('Danemarca', 'woocommerce'),
                'DM' => __('Dominica', 'woocommerce'),
                'DO' => __('Republica Dominicană', 'woocommerce'),
                'DZ' => __('Algeria', 'woocommerce'),
                'EC' => __('Ecuador', 'woocommerce'),
                'EE' => __('Estonia', 'woocommerce'),
                'EG' => __('Egipt', 'woocommerce'),
                'EH' => __('Sahara Occidentală', 'woocommerce'),
                'ER' => __('Eritreea', 'woocommerce'),
                'ES' => __('Spania', 'woocommerce'),
                'ET' => __('Etiopia', 'woocommerce'),
                'FI' => __('Finlanda', 'woocommerce'),
                'FJ' => __('Fiji', 'woocommerce'),
                'FK' => __('Insulele Falkland', 'woocommerce'),
                'FM' => __('Micronezia', 'woocommerce'),
                'FO' => __('Insulele Feroe', 'woocommerce'),
                'FR' => __('Franța', 'woocommerce'),
                'GA' => __('Gabon', 'woocommerce'),
                'GB' => __('Regatul Unit', 'woocommerce'),
                'GD' => __('Grenada', 'woocommerce'),
                'GE' => __('Georgia', 'woocommerce'),
                'GF' => __('Guyana Franceză', 'woocommerce'),
                'GG' => __('Guernsey', 'woocommerce'),
                'GH' => __('Ghana', 'woocommerce'),
                'GI' => __('Gibraltar', 'woocommerce'),
                'GL' => __('Groenlanda', 'woocommerce'),
                'GM' => __('Gambia', 'woocommerce'),
                'GN' => __('Guinea', 'woocommerce'),
                'GP' => __('Guadeloupe', 'woocommerce'),
                'GQ' => __('Guinea Ecuatorială', 'woocommerce'),
                'GR' => __('Grecia', 'woocommerce'),
                'GS' => __('Georgia de Sud și Insulele Sandwich de Sud', 'woocommerce'),
                'GT' => __('Guatemala', 'woocommerce'),
                'GU' => __('Guam', 'woocommerce'),
                'GW' => __('Guinea-Bissau', 'woocommerce'),
                'GY' => __('Guyana', 'woocommerce'),
                'HK' => __('Hong Kong', 'woocommerce'),
                'HM' => __('Insula Heard și Insulele McDonald', 'woocommerce'),
                'HN' => __('Honduras', 'woocommerce'),
                'HR' => __('Croația', 'woocommerce'),
                'HT' => __('Haiti', 'woocommerce'),
                'HU' => __('Ungaria', 'woocommerce'),
                'ID' => __('Indonezia', 'woocommerce'),
                'IE' => __('Irlanda', 'woocommerce'),
                'IL' => __('Israel', 'woocommerce'),
                'IM' => __('Insula Man', 'woocommerce'),
                'IN' => __('India', 'woocommerce'),
                'IO' => __('Teritoriul Britanic din Oceanul Indian', 'woocommerce'),
                'IQ' => __('Irak', 'woocommerce'),
                'IR' => __('Iran', 'woocommerce'),
                'IS' => __('Islanda', 'woocommerce'),
                'IT' => __('Italia', 'woocommerce'),
                'JE' => __('Jersey', 'woocommerce'),
                'JM' => __('Jamaica', 'woocommerce'),
                'JO' => __('Iordania', 'woocommerce'),
                'JP' => __('Japonia', 'woocommerce'),
                'KE' => __('Kenya', 'woocommerce'),
                'KG' => __('Kârgâzstan', 'woocommerce'),
                'KH' => __('Cambodgia', 'woocommerce'),
                'KI' => __('Kiribati', 'woocommerce'),
                'KM' => __('Comori', 'woocommerce'),
                'KN' => __('Saint Kitts și Nevis', 'woocommerce'),
                'KP' => __('Coreea de Nord', 'woocommerce'),
                'KR' => __('Coreea de Sud', 'woocommerce'),
                'KW' => __('Kuweit', 'woocommerce'),
                'KY' => __('Insulele Cayman', 'woocommerce'),
                'KZ' => __('Kazahstan', 'woocommerce'),
                'LA' => __('Laos', 'woocommerce'),
                'LB' => __('Liban', 'woocommerce'),
                'LC' => __('Saint Lucia', 'woocommerce'),
                'LI' => __('Liechtenstein', 'woocommerce'),
                'LK' => __('Sri Lanka', 'woocommerce'),
                'LR' => __('Liberia', 'woocommerce'),
                'LS' => __('Lesotho', 'woocommerce'),
                'LT' => __('Lituania', 'woocommerce'),
                'LU' => __('Luxemburg', 'woocommerce'),
                'LV' => __('Letonia', 'woocommerce'),
                'LY' => __('Libia', 'woocommerce'),
                'MA' => __('Maroc', 'woocommerce'),
                'MC' => __('Monaco', 'woocommerce'),
                'MD' => __('Moldova', 'woocommerce'),
                'ME' => __('Muntenegru', 'woocommerce'),
                'MF' => __('Saint Martin', 'woocommerce'),
                'MG' => __('Madagascar', 'woocommerce'),
                'MH' => __('Insulele Marshall', 'woocommerce'),
                'MK' => __('Macedonia de Nord', 'woocommerce'),
                'ML' => __('Mali', 'woocommerce'),
                'MM' => __('Myanmar', 'woocommerce'),
                'MN' => __('Mongolia', 'woocommerce'),
                'MO' => __('Macao', 'woocommerce'),
                'MP' => __('Insulele Mariane de Nord', 'woocommerce'),
                'MQ' => __('Martinica', 'woocommerce'),
                'MR' => __('Mauritania', 'woocommerce'),
                'MS' => __('Montserrat', 'woocommerce'),
                'MT' => __('Malta', 'woocommerce'),
                'MU' => __('Mauritius', 'woocommerce'),
                'MV' => __('Maldive', 'woocommerce'),
                'MW' => __('Malawi', 'woocommerce'),
                'MX' => __('Mexic', 'woocommerce'),
                'MY' => __('Malaezia', 'woocommerce'),
                'MZ' => __('Mozambic', 'woocommerce'),
                'NA' => __('Namibia', 'woocommerce'),
                'NC' => __('Noua Caledonie', 'woocommerce'),
                'NE' => __('Niger', 'woocommerce'),
                'NF' => __('Insula Norfolk', 'woocommerce'),
                'NG' => __('Nigeria', 'woocommerce'),
                'NI' => __('Nicaragua', 'woocommerce'),
                'NL' => __('Olanda', 'woocommerce'),
                'NO' => __('Norvegia', 'woocommerce'),
                'NP' => __('Nepal', 'woocommerce'),
                'NR' => __('Nauru', 'woocommerce'),
                'NU' => __('Niue', 'woocommerce'),
                'NZ' => __('Noua Zeelandă', 'woocommerce'),
                'OM' => __('Oman', 'woocommerce'),
                'PA' => __('Panama', 'woocommerce'),
                'PE' => __('Peru', 'woocommerce'),
                'PF' => __('Polinezia Franceză', 'woocommerce'),
                'PG' => __('Papua Noua Guinee', 'woocommerce'),
                'PH' => __('Filipine', 'woocommerce'),
                'PK' => __('Pakistan', 'woocommerce'),
                'PL' => __('Polonia', 'woocommerce'),
                'PM' => __('Saint Pierre și Miquelon', 'woocommerce'),
                'PN' => __('Insulele Pitcairn', 'woocommerce'),
                'PR' => __('Puerto Rico', 'woocommerce'),
                'PS' => __('Palestina', 'woocommerce'),
                'PT' => __('Portugalia', 'woocommerce'),
                'PW' => __('Palau', 'woocommerce'),
                'PY' => __('Paraguay', 'woocommerce'),
                'QA' => __('Qatar', 'woocommerce'),
                'RE' => __('Réunion', 'woocommerce'),
                'RS' => __('Serbia', 'woocommerce'),
                'RU' => __('Rusia', 'woocommerce'),
                'RW' => __('Rwanda', 'woocommerce'),
                'SA' => __('Arabia Saudită', 'woocommerce'),
                'SB' => __('Insulele Solomon', 'woocommerce'),
                'SC' => __('Seychelles', 'woocommerce'),
                'SD' => __('Sudan', 'woocommerce'),
                'SE' => __('Suedia', 'woocommerce'),
                'SG' => __('Singapore', 'woocommerce'),
                'SH' => __('Sfânta Elena', 'woocommerce'),
                'SI' => __('Slovenia', 'woocommerce'),
                'SJ' => __('Svalbard și Jan Mayen', 'woocommerce'),
                'SK' => __('Slovacia', 'woocommerce'),
                'SL' => __('Sierra Leone', 'woocommerce'),
                'SM' => __('San Marino', 'woocommerce'),
                'SN' => __('Senegal', 'woocommerce'),
                'SO' => __('Somalia', 'woocommerce'),
                'SR' => __('Suriname', 'woocommerce'),
                'SS' => __('Sudanul de Sud', 'woocommerce'),
                'ST' => __('São Tomé și Príncipe', 'woocommerce'),
                'SV' => __('El Salvador', 'woocommerce'),
                'SX' => __('Sint Maarten', 'woocommerce'),
                'SY' => __('Siria', 'woocommerce'),
                'SZ' => __('Eswatini', 'woocommerce'),
                'TC' => __('Insulele Turks și Caicos', 'woocommerce'),
                'TD' => __('Ciad', 'woocommerce'),
                'TF' => __('Teritoriile Australe și Antarctice Franceze', 'woocommerce'),
                'TG' => __('Togo', 'woocommerce'),
                'TH' => __('Thailanda', 'woocommerce'),
                'TJ' => __('Tadjikistan', 'woocommerce'),
                'TK' => __('Tokelau', 'woocommerce'),
                'TL' => __('Timorul de Est', 'woocommerce'),
                'TM' => __('Turkmenistan', 'woocommerce'),
                'TN' => __('Tunisia', 'woocommerce'),
                'TO' => __('Tonga', 'woocommerce'),
                'TR' => __('Turcia', 'woocommerce'),
                'TT' => __('Trinidad și Tobago', 'woocommerce'),
                'TV' => __('Tuvalu', 'woocommerce'),
                'TW' => __('Taiwan', 'woocommerce'),
                'TZ' => __('Tanzania', 'woocommerce'),
                'UA' => __('Ucraina', 'woocommerce'),
                'UG' => __('Uganda', 'woocommerce'),
                'UM' => __('Insulele Îndepărtate ale Statelor Unite', 'woocommerce'),
                'US' => __('Statele Unite', 'woocommerce'),
                'UY' => __('Uruguay', 'woocommerce'),
                'UZ' => __('Uzbekistan', 'woocommerce'),
                'VA' => __('Vatican', 'woocommerce'),
                'VC' => __('Saint Vincent și Grenadine', 'woocommerce'),
                'VE' => __('Venezuela', 'woocommerce'),
                'VG' => __('Insulele Virgine Britanice', 'woocommerce'),
                'VI' => __('Insulele Virgine Americane', 'woocommerce'),
                'VN' => __('Vietnam', 'woocommerce'),
                'VU' => __('Vanuatu', 'woocommerce'),
                'WF' => __('Wallis și Futuna', 'woocommerce'),
                'WS' => __('Samoa', 'woocommerce'),
                'YE' => __('Yemen', 'woocommerce'),
                'YT' => __('Mayotte', 'woocommerce'),
                'ZA' => __('Africa de Sud', 'woocommerce'),
                'ZM' => __('Zambia', 'woocommerce'),
                'ZW' => __('Zimbabwe', 'woocommerce')
            ),
            'default'     => 'RO'
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
            echo '<p><strong>' . __('Adresa Companie', 'woocommerce') . ':</strong> ' . get_post_meta($order->get_id(), '_billing_company_address', true) . '</p>';
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
            'billing_company_address',
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
                'billing_company_city' => __('Oraș Companie', 'woocommerce'),
                'billing_company_address' => __('Adresa Companie', 'woocommerce'),
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
                        // Add required attribute and asterisk to company fields (except bank and IBAN)
                        $('.company-field').each(function() {
                            var fieldName = $(this).attr('name');
                            // Skip bank and IBAN fields - they are optional
                            if (fieldName !== 'billing_banca' && fieldName !== 'billing_cont_iban') {
                                $(this).attr('required', 'required');
                                var $label = $(this).closest('.form-row').find('label');
                                if (!$label.find('.required').length) {
                                    $label.append(' <abbr class="required" title="required">*</abbr>');
                                }
                            } else {
                                // Ensure bank and IBAN fields never have asterisk or required attribute
                                $(this).removeAttr('required');
                                $(this).closest('.form-row').find('label .required').remove();
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

                // Romanian counties (județe) list
                var romanianCounties = {
                    'AB': 'Alba',
                    'AR': 'Arad',
                    'AG': 'Argeș',
                    'BC': 'Bacău',
                    'BH': 'Bihor',
                    'BN': 'Bistrița-Năsăud',
                    'BT': 'Botoșani',
                    'BV': 'Brașov',
                    'BR': 'Brăila',
                    'BZ': 'Buzău',
                    'CS': 'Caraș-Severin',
                    'CL': 'Călărași',
                    'CJ': 'Cluj',
                    'CT': 'Constanța',
                    'CV': 'Covasna',
                    'DB': 'Dâmbovița',
                    'DJ': 'Dolj',
                    'GL': 'Galați',
                    'GR': 'Giurgiu',
                    'GJ': 'Gorj',
                    'HR': 'Harghita',
                    'HD': 'Hunedoara',
                    'IL': 'Ialomița',
                    'IS': 'Iași',
                    'IF': 'Ilfov',
                    'MM': 'Maramureș',
                    'MH': 'Mehedinți',
                    'MS': 'Mureș',
                    'NT': 'Neamț',
                    'OT': 'Olt',
                    'PH': 'Prahova',
                    'SM': 'Satu Mare',
                    'SJ': 'Sălaj',
                    'SB': 'Sibiu',
                    'SV': 'Suceava',
                    'TR': 'Teleorman',
                    'TM': 'Timiș',
                    'TL': 'Tulcea',
                    'VS': 'Vaslui',
                    'VL': 'Vâlcea',
                    'VN': 'Vrancea',
                    'B': 'București'
                };

                // Function to update state options based on selected country
                function updateStateOptions() {
                    var $stateField = $('#billing_company_state');
                    var $countryField = $('#billing_company_country');
                    var selectedCountry = $countryField.val();
                    var $stateRow = $stateField.closest('.form-row');
                    
                    if (selectedCountry === 'RO') {
                        // For Romania, use dropdown with counties
                        if ($stateField.prop('tagName') !== 'SELECT') {
                            // Replace input with select
                            var currentValue = $stateField.val();
                            var selectHtml = '<select name="billing_company_state" id="billing_company_state" class="select company-field" data-placeholder="Selectați județul...">';
                            selectHtml += '<option value="">Selectați județul...</option>';
                            
                            // Add Romanian counties
                            $.each(romanianCounties, function(code, name) {
                                var selected = (currentValue === code) ? ' selected' : '';
                                selectHtml += '<option value="' + code + '"' + selected + '>' + name + '</option>';
                            });
                            selectHtml += '</select>';
                            
                            $stateField.replaceWith(selectHtml);
                        } else {
                            // Already a select, just update options
                            $stateField.empty();
                            $stateField.append('<option value="">Selectați județul...</option>');
                            
                            // Add Romanian counties
                            $.each(romanianCounties, function(code, name) {
                                $stateField.append('<option value="' + code + '">' + name + '</option>');
                            });
                        }
                    } else {
                        // For other countries, use text input
                        if ($stateField.prop('tagName') !== 'INPUT') {
                            // Replace select with input
                            var currentValue = $stateField.val();
                            var inputHtml = '<input type="text" name="billing_company_state" id="billing_company_state" placeholder="Județul sau statul companiei" class="input-text company-field" value="' + currentValue + '">';
                            
                            $stateField.replaceWith(inputHtml);
                        }
                    }
                    
                    // Re-apply company field styling and validation
                    var $newStateField = $('#billing_company_state');
                    if ($('#billing_company_details').is(':checked')) {
                        // State field is always required when company details are checked
                        $newStateField.attr('required', 'required');
                    }
                }

                // Update state options when country changes
                $(document).on('change', '#billing_company_country', function() {
                    updateStateOptions();
                });

                // Initialize state options on page load
                updateStateOptions();

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
                            var fieldName = $field.attr('name');
                            
                            // Skip validation for bank and IBAN fields - they are optional
                            if (fieldName === 'billing_banca' || fieldName === 'billing_cont_iban') {
                                return true; // continue to next iteration
                            }
                            
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

                // Real-time validation (using event delegation for dynamic fields)
                $(document).on('blur change', '.company-field', function() {
                    var $field = $(this);
                    var $row = $field.closest('.form-row');
                    var fieldName = $field.attr('name');
                    
                    if ($('#billing_company_details').is(':checked')) {
                        // Skip validation for bank and IBAN fields - they are optional
                        if (fieldName === 'billing_banca' || fieldName === 'billing_cont_iban') {
                            $row.removeClass('woocommerce-invalid'); // Always remove error styling for optional fields
                            return;
                        }
                        
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
