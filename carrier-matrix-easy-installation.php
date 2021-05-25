<?php

/*

Plugin Name: Carrier Matrix Easy Installation

Description: Use a shortcode to install the Carrier Matrix forms and results areas on your pages or posts.

Author: Apis Productions

Author URI: http://www.apisproductions.com/

Version: 1.1.0

*/



class CarrierMatrixEasyInstallation {

    private $carrier_matrix_options;

    public function __construct() {

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'carrier_matrix_add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'carrier_matrix_page_init' ) );
            add_action( 'init', array( $this, 'carrier_matrix_gutenberg_block_admin') );
            add_action( 'admin_enqueue_scripts', array( $this, 'cm_enqueue_color_picker' ) );
        }
        add_shortcode('carrier-matrix', array( $this, 'carrier_matrix_shortcode_handler' ));
    }


    public function cm_enqueue_color_picker($hook_suffix) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'cm-color-picker', plugins_url('/js/cm-color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
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
        $this->carrier_matrix_options = get_option( 'carrier_matrix_options' ); ?>

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
            'carrier_matrix_options',
            array( $this, 'carrier_matrix_sanitize' )
        );

        add_settings_section(
            'carrier_matrix_settings_section',
            'Settings',
            array( $this, 'carrier_matrix_section_settings' ),
            'carrier-matrix-easy-installation-admin'
        );

        add_settings_field(
            'cm_api_key',
            'API Key',
            array( $this, 'cm_api_key_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_settings_section'
        );

        add_settings_field(
            'cm_agency_id',
            'Agency ID',
            array( $this, 'cm_agency_id_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_settings_section'
        );

        add_settings_section(
            'carrier_matrix_default_styling_section',
            'Default Styling',
            array( $this, 'carrier_matrix_section_default_styling' ),
            'carrier-matrix-easy-installation-admin'
        );


        add_settings_field(
            'cm_button_text',
            'Button Text<br><small>[button-text]</small>',
            array( $this, 'cm_button_text_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );

        add_settings_field(
            'cm_results_height',
            'Min height of the results table<br><small>[button-text]</small>',
            array( $this, 'cm_results_height_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );

        add_settings_field(
            'cm_heading_color',
            'Headings Background Color<br><small>[heading-color]</small>',
            array( $this, 'cm_heading_color_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );

        add_settings_field(
            'cm_heading_open_color',
            'Heading Open Background Color<br><small>[heading-open-color]</small>',
            array( $this, 'cm_heading_open_color_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );

        add_settings_field(
            'cm_heading_hover_color',
            'Heading Hover Background Color<br><small>[heading-hover-color]</small>',
            array( $this, 'cm_heading_hover_color_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );

        add_settings_field(
            'cm_heading_text_color',
            'Heading Text Color<br><small>[heading-text-color]</small>',
            array( $this, 'cm_heading_text_color_callback' ),
            'carrier-matrix-easy-installation-admin',
            'carrier_matrix_default_styling_section'
        );
    }

    public function carrier_matrix_sanitize($input) {
        $sanitary_values = array();

        $values = array('cm_api_key', 'cm_agency_id', 'cm_button_text', 'cm_results_height', 'cm_heading_color', 'cm_heading_open_color', 'cm_heading_hover_color', 'cm_heading_text_color');

        foreach ($values as $value) {
            if ( isset( $input[$value] ) ) {
                $sanitary_values[$value] = sanitize_text_field( $input[$value] );
            }
        }
        
        return $sanitary_values;
    }

    public function carrier_matrix_section_settings() {
        echo '<p>Place the Carrier Matrix shortcode on the pages that you want to give users access to the Carrier Matrix search application. <br>Use either the "form" attribute or the "results" attribute to specify if the shortcode will show the search form or results, and then accompanied by what type of form or results ("product", "abr", "report", or "contact"). <br>e.g.: <strong><br>[carrier-matrix form="product" button_text="Search"]<br>[carrier-matrix results="product"]</strong></p>';
    }


    public function carrier_matrix_section_default_styling() {
        echo '<p>Add site-wide defaults for the styling of the Carrier Matrix, each option can be overwritten within the shortcode as attributes (specified in brackets) if you would like to change the single instance.</p>';
    }


    public function cm_api_key_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_options[cm_api_key]" id="cm_api_key" value="%s">',
            isset( $this->carrier_matrix_options['cm_api_key'] ) ? esc_attr( $this->carrier_matrix_options['cm_api_key']) : ''
        );
    }

    public function cm_agency_id_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_options[cm_agency_id]" id="cm_agency_id" value="%s">',
            isset( $this->carrier_matrix_options['cm_agency_id'] ) ? esc_attr( $this->carrier_matrix_options['cm_agency_id']) : ''
        );
    }

    public function cm_button_text_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_options[cm_button_text]" id="cm_button_text" value="%s">',
            isset( $this->carrier_matrix_options['cm_button_text'] ) ? esc_attr( $this->carrier_matrix_options['cm_button_text']) : 'Search'
        );
    }

    public function cm_results_height_callback() {
        printf(
            '<input class="regular-text" type="text" name="carrier_matrix_options[cm_results_height]" id="cm_results_height" value="%s">',
            isset( $this->carrier_matrix_options['cm_results_height'] ) ? esc_attr( $this->carrier_matrix_options['cm_results_height']) : '96vh'
        );
    }

    public function cm_heading_color_callback() {
        printf(
            '<input class="cm-color-picker" type="text" name="carrier_matrix_options[%s]" id="%s" value="%s">',
            'cm_heading_color', 'cm_heading_color', isset( $this->carrier_matrix_options['cm_heading_color'] ) ? esc_attr( $this->carrier_matrix_options['cm_heading_color']) : '#6c757d'
        );
    }

    public function cm_heading_open_color_callback() {
        printf(
            '<input class="cm-color-picker" type="text" name="carrier_matrix_options[%s]" id="%s" value="%s">',
            'cm_heading_open_color', 'cm_heading_open_color', isset( $this->carrier_matrix_options['cm_heading_open_color'] ) ? esc_attr( $this->carrier_matrix_options['cm_heading_open_color']) : '#495057'
        );
    }

    public function cm_heading_hover_color_callback() {
        printf(
            '<input class="cm-color-picker" type="text" name="carrier_matrix_options[%s]" id="%s" value="%s">',
            'cm_heading_hover_color', 'cm_heading_hover_color', isset( $this->carrier_matrix_options['cm_heading_hover_color'] ) ? esc_attr( $this->carrier_matrix_options['cm_heading_hover_color']) : '#343a40'
        );
    }

    public function cm_heading_text_color_callback() {
        printf(
            '<input class="cm-color-picker" type="text" name="carrier_matrix_options[%s]" id="%s" value="%s">',
            'cm_heading_text_color', 'cm_heading_text_color', isset( $this->carrier_matrix_options['cm_heading_text_color'] ) ? esc_attr( $this->carrier_matrix_options['cm_heading_text_color']) : '#ffffff'
        );
    }

    public function carrier_matrix_shortcode_handler($atts) {
        $carrier_matrix_options = get_option('carrier_matrix_options');
        $a = shortcode_atts( array(
            'form' => null, // product, abr, contact, or report
            'results' => null, // product, abr, contact, or report
            'button_text' => $carrier_matrix_options['cm_button_text'],
            'results_height' => $carrier_matrix_options['cm_results_height'],
            'cm_heading_color' => $carrier_matrix_options['cm_heading_color'],
            'cm_heading_hover_color' => $carrier_matrix_options['cm_heading_hover_color'],
            'cm_heading_open_color' => $carrier_matrix_options['cm_heading_open_color'],
            'cm_heading_text_color' => $carrier_matrix_options['cm_heading_text_color'],
        ), $atts );

        return $this->carrier_matrix_handler_callback($a['form'], $a['results'], $a['button_text'], $a['results_height'], $a['cm_heading_color'], $a['cm_heading_hover_color'], $a['cm_heading_open_color'], $a['cm_heading_text_color']);

    }

    public function carrier_matrix_block_handler($atts) {
        return $this->carrier_matrix_handler_callback($atts['form'], $atts['results'], $atts['button_text'], $atts['results_height'], $atts['cm_heading_color'], $atts['cm_heading_hover_color'], $atts['cm_heading_open_color'], $atts['cm_heading_text_color']);
    }

    public function carrier_matrix_handler_callback($form, $results, $button_text, $results_height, $cm_heading_color, $cm_heading_hover_color, $cm_heading_open_color, $cm_heading_text_color) {
        wp_register_script( 'carrier-matrix-app', 'https://idacmstaging.wpengine.com/carrier-matrix/app.js' , '', time(), true );
        wp_enqueue_script('carrier-matrix-app');
        $carrier_matrix_options = get_option('carrier_matrix_options');

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
            echo '<carrier-matrix form="'.$form.'" button-text="'.$button_text.'" heading-color="'.$cm_heading_color.'" heading-open-color="'.$cm_heading_open_color.'" heading-hover-color="'.$cm_heading_hover_color.'" heading-text-color="'.$cm_heading_text_color.'" api-key="'.$carrier_matrix_options['cm_api_key'].'" agency-id="'.$carrier_matrix_options['cm_agency_id'].'"></carrier-matrix>';
        } else if (!empty($results)) {
            echo '<carrier-matrix  results="'.$results.'" results-height="'.$results_height.'"></carrier-matrix>';
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