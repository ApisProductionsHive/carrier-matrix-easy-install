<?php

/*

Plugin Name: Carrier Matrix Easy Installation

Description: Use a shortcode to install the Carrier Matrix forms and results areas on your pages or posts.

Author: Apis Productions

Author URI: http://www.apisproductions.com/

Version: 1.0.1

*/



class CarrierMatrixEasyInstallation {

    private $carrier_matrix_options;

    public function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'carrier_matrix_add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'carrier_matrix_page_init' ) );
            add_action( 'init', array( $this, 'carrier_matrix_gutenberg_block_admin') );
        }
        add_shortcode('carrier-matrix', array( $this, 'carrier_matrix_shortcode_handler' ));
    }

    public function carrier_matrix_gutenberg_block_admin() {

        // Skip block registration if Gutenberg is not enabled/merged.
        if (!function_exists('register_block_type')) {
            return;
        }
        $dir = dirname(__FILE__);

        $index_js = 'gutenberg-cm-block.js';
        wp_register_script(
            'gutenberg-cm-block',
            plugins_url($index_js, __FILE__),
            array(
                'wp-blocks',
                'wp-i18n',
                'wp-element',
                'wp-components'
            ),
            filemtime("$dir/$index_js")
        );

        register_block_type('carrier-matrix/installation', array(
            'editor_script' => 'carrier_matrix_handler_callback',
            'render_callback' => array( $this, 'carrier_matrix_block_handler'),
            'attributes' => [
                'form' => [
                    'default' => null
                ],
                'results' => [
                    'default' => null
                ],
                'button_text' => [
                    'default' => 'Search'
                ],
            ]
        ));
    }

    public function carrier_matrix_add_plugin_page() {
        add_options_page(
            'Carrier Matrix Easy Installation',
            'Carrier Matrix Easy Installation',
            'manage_options',
            'carrier-matrix-easy-installation',
            array( $this, 'carrier_matrix_create_admin_page' )
        );
    }

    public function carrier_matrix_create_admin_page() {
        $this->carrier_matrix_options = get_option( 'carrier_matrix_option_name' ); ?>

        <div class="wrap">
            <h2>Carrier Matrix Easy Installation</h2>
            <p></p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'carrier_matrix_option_group' );
                do_settings_sections( 'carrier-matrix-easy-installation-admin' );
                submit_button();
                ?>
            </form>
        </div>
    <?php }

    public function carrier_matrix_page_init() {
        register_setting(
            'carrier_matrix_option_group',
            'carrier_matrix_option_name',
            array( $this, 'carrier_matrix_sanitize' )
        );

        add_settings_section(
            'carrier_matrix_setting_section',
            'Settings',
            array( $this, 'carrier_matrix_section_info' ),
            'carrier-matrix-easy-installation-admin'
        );

        add_settings_field(
            'cm_api_key',
            'API Key',
            array( $this, 'cm_api_key_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_setting_section'
        );

        add_settings_field(
            'cm_agency_id',
            'Agency ID',
            array( $this, 'cm_agency_id_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_setting_section'
        );
    }

    public function carrier_matrix_sanitize($input) {
        $sanitary_values = array();
        if ( isset( $input['cm_api_key'] ) ) {
            $sanitary_values['cm_api_key'] = sanitize_text_field( $input['cm_api_key'] );
        }

        if ( isset( $input['cm_agency_id'] ) ) {
            $sanitary_values['cm_agency_id'] = sanitize_text_field( $input['cm_agency_id'] );
        }

        return $sanitary_values;
    }

    public function carrier_matrix_section_info() {
        echo '<p>Place the Carrier Matrix shortcode on the pages that you want to give users access to the Carrier Matrix search application. <br>Use either the "form" attribute or the "results" attribute to specify if the shortcode will show the search form or results, and then accompanied by what type of form or results ("product", "abr", "report", or "contact"). <br>e.g.: <strong><br>[carrier-matrix form="product" button_text="Search"]<br>[carrier-matrix results="product"]</strong></p>';
    }

    public function cm_api_key_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_option_name[cm_api_key]" id="cm_api_key" value="%s">',
            isset( $this->carrier_matrix_options['cm_api_key'] ) ? esc_attr( $this->carrier_matrix_options['cm_api_key']) : ''
        );
    }

    public function cm_agency_id_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_option_name[cm_agency_id]" id="cm_agency_id" value="%s">',
            isset( $this->carrier_matrix_options['cm_agency_id'] ) ? esc_attr( $this->carrier_matrix_options['cm_agency_id']) : ''
        );
    }

    public function carrier_matrix_shortcode_handler($atts) {

        $a = shortcode_atts( array(
            'form' => null, // product, abr, contact, or report
            'results' => null, // product, abr, contact, or report
            'button_text' => 'Search',
            'results_height' => null,
        ), $atts );

        return $this->carrier_matrix_handler_callback($a['form'], $a['results'], $a['button_text'], $a['resultsHeight']);

    }

    public function carrier_matrix_block_handler($atts) {
        return $this->carrier_matrix_handler_callback($atts['form'], $atts['results'], $atts['button_text']);
    }

    public function carrier_matrix_handler_callback($form, $results, $button_text, $results_height) {
        wp_register_script( 'carrier-matrix-app', 'https://idacmstaging.wpengine.com/carrier-matrix/app.js' , '', time(), true );
        wp_enqueue_script('carrier-matrix-app');
        $carrier_matrix_options = get_option('carrier_matrix_option_name');

        ob_start();
        if (empty($form) && empty($results)) {
            echo 'The "form" attribute or "results" attribute in your shortcode is required, for help, please contact your website administrator.';
            return ob_get_clean();
        }
        if (!empty($form) && !empty($results)) {
            echo 'Only "form" or "results" should be specified in your shortcode, for help, please contact your website administrator.';
            return ob_get_clean();
        }
        if (empty($carrier_matrix_options['cm_api_key']) && empty($carrier_matrix_options['cm_agency_id'])) {
            echo 'The API key and the Agency ID both need to be set in options-general.php?page=carrier-matrix-easy-installation, for help, please contact your website administrator.';
            return ob_get_clean();
        }

        if (!empty($form)) {
            echo '<carrier-matrix form="'.$form.'" button-text="'.$button_text.'" api-key="'.$carrier_matrix_options['cm_api_key'].'" agency-id="'.$carrier_matrix_options['cm_agency_id'].'"></carrier-matrix>';
        } else if (!empty($results)) {
            echo '<carrier-matrix  results="'.$results.'" resultsHeight="'.$results_height.'"></carrier-matrix>';
        }
        return ob_get_clean();
    }

}

$carrier_matrix = new CarrierMatrixEasyInstallation();


require 'plugin-update-checker/plugin-update-checker.php';
$apisUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/ApisProductionsHive/carrier-matrix-easy-install/',
    __FILE__,
    'carrier-matrix-easy-installation'
);


$apisUpdateChecker->setBranch('main');
$apisUpdateChecker->setAuthentication('55fc9310055924d57bd72f9d496d820f3036829e');

?>