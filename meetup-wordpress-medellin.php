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
        $report_button .= mwpm_generate_button_for_post();
        $report_button .= '</p>';

        $report_modal = mwpm_render_report_modal();

        return $report_button . $content . $report_modal;
    }

    return $content;
}
add_filter( 'the_content', 'mwpm_add_report_button_to_content' );

function mwpm_generate_button_for_post() {
    $button  = '';
    $button .= '<button id="reportBtn">';
    $button .= '<span class="dashicons dashicons-flag"></span>';
    $button .= ' ';
    $button .= 'Reportar';
    $button .= '</button>';

    return $button;
}

/**
 * Crea el código HTML para el cuadro de diálogo que los usuarios podrán utilizar
 * para reportar una entrada.
 *
 * @since 1.0.0
 */
function mwpm_render_report_modal() {
    $modal = '
    <!-- Report Post Modal -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <span class="report-header">
                <h2>You are about to report "' . get_the_title() . '"</h2>
            </span>
            <span class="close">&times;</span>
            ' . mwpm_get_modal_form() . '
        </div>
    </div>';


    return $modal;
}

/**
 * Construye el código HTML para el formulario que los usuarios podrán utilizar para
 * describir las razones por las que están reportando una entrada.
 *
 * @since 1.0.0
 */
function mwpm_get_modal_form() {
    $form = '
    <form action="" method="post">
        <input type="hidden" name="report[post_id]" value="' . get_the_ID() . '" />
        <p>' . mwpm_render_name_input_field() . '</p>
        <p>' . mwpm_render_email_input_field() . '</p>

        <p>
            <label for="mwpm-report-form__reason">Please enter the reasons to report this post:</label>
            <textarea id="mwpm-report-form__reason" name="report[reason]" value="" placeholder="Additional info." required></textarea>
        </p>
        <p>
            <input class="btn" type="submit" value="Report" />
        </p>
    </form>';

    return $form;
}

/**
 * Crea el código HTML para el campo del nombre de quien reporta.
 *
 * @since 1.0.0
 */
function mwpm_render_name_input_field() {
    $field  = '';
    $field .= '<label for="mwpm-report-form__name">Your Name</label>';
    $field .= '<input id="mwpm-report-form__name" type="text" name="report[name]" placeholder="María Pérez" required/>';

    return $field;
}

/**
 * Crea el código HTML para el campo del correo electrónico de quien reporta.
 *
 * @since 1.0.0
 */
function mwpm_render_email_input_field() {
    $field  = '';
    $field .= '<label for="mwpm-report-form__email">You Email</label>';
    $field .= '<input id="mwpm-report-form__email" type="email" name="report[email]" placeholder="maria.perez@example.org" required/>';

    return $field;
}
