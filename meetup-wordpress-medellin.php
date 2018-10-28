<?php
/**
 * Plugin Name: Meetup Wordpress Medellín 2018
 * Plugin URI: https://github.com/smilingrobots/meetup-wp-medellin
 * Description: Plugin de prueba para el taller de introducción al desarrollo de plugins para WordPress.
 * Version: 1.0.0
 */

function mwpm_add_report_button_to_content( $content ) {
    // El formulario para reportar entradas solo será mostrado en páginas y
    // publicaciones individuales.
    if ( is_single() || is_page() ) {
        $report_button  = '';
        $report_button .= '<p>';
        $report_button .= '-- Reportar --';
        $report_button .= '</p>';

        return $report_button . $content;
    }

    return $content;
}
add_filter( 'the_content', 'mwpm_add_report_button_to_content' );
