<?php
/**
 * Plugin Name: Meetup Wordpress Medellín 2018
 * Plugin URI: https://github.com/smilingrobots/meetup-wp-medellin
 * Description: Plugin de prueba para el taller de introducción al desarrollo de plugins para WordPress.
 * Version: 1.0.0
 */

/**
 * Función asociada a la acción wp_enqueue_scripts para registrar las hojas
 * de estilo y los scripts que el plugin necesita en el frontend.
 *
 * @since 1.0.0
 */
function mwpm_enqueue_scripts_and_styles() {
    // Las hojas de estilo y scripts solo seran necesarios en páginas y publicaciones individuales.
    if ( is_single() || is_page() ) {
        wp_enqueue_style(
            'mwpm-styles',
            plugins_url( '/style.css', __FILE__ )
        );

        wp_enqueue_script(
            'mwpm-script',
            plugins_url( '/mwpm_script.js', __FILE__ ),
            array(),
            '1.0.0',
            true // $in_footer = true para que el <script> se genere al final de la página.
        );
    }
}
add_action( 'wp_enqueue_scripts', 'mwpm_enqueue_scripts_and_styles' );

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
    // Inicializar variables y obtener los datos del usuario actual
    $username   = '';
    $user_email = '';

    // Validar que el usuario esté loggeado.
    if( is_user_logged_in() ) {
        // Obtener los datos del usuario actual.
        $current_user = wp_get_current_user();

        $username   = $current_user->user_login;
        $user_email = $current_user->user_email;
    }

    $form = '
    <form action="" method="post">
        <input type="hidden" name="report[post_id]" value="' . get_the_ID() . '" />
        <p>' . mwpm_render_name_input_field( $username ) . '</p>
        <p>' . mwpm_render_email_input_field( $user_email ) . '</p>

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
function mwpm_render_name_input_field( $username ) {
    $field  = '';

    if ( $username ) {
        $field .= '<label for="mwpm-report-form__name">Your Name: <span>' . $username . '</span></label>';
        $field .= '<input id="mwpm-report-form__name" type="hidden" name="report[name]" value="' . $username . '" />';
    } else {
        $field .= '<label for="mwpm-report-form__name">Your Name</label>';
        $field .= '<input id="mwpm-report-form__name" type="text" name="report[name]" placeholder="María Pérez" required />';
    }

    return $field;
}

/**
 * Crea el código HTML para el campo del correo electrónico de quien reporta.
 *
 * @since 1.0.0
 */
function mwpm_render_email_input_field( $user_email ) {
    $field  = '';

    if ( $user_email ) {
        $field .= '<label for="mwpm-report-form__email">You Email: <span>' . $user_email . '</span></label>';
        $field .= '<input id="mwpm-report-form__name" type="hidden" name="report[email]" value="' . $user_email . '" />';
    } else {
        $field .= '<label for="mwpm-report-form__email">You Email</label>';
        $field .= '<input id="mwpm-report-form__email" type="email" name="report[email]" placeholder="maria.perez@example.org" required />';
    }

    return $field;
}

/**
 * Envía una notificación al administrador cuando la petición actual
 * incluye información enviada desde el formulario para reportar
 * entradas.
 *
 * @since 1.0.0
 */
function mwpm_maybe_send_report() {
    // Validar que existan los datos del reporte.
    if ( empty( $_POST['report'] ) ) {
        return;
    }

    // Remover slashes (/) de los datos del reporte suministrados por el
    // usuario en el formulario.
    $report = wp_unslash( $_POST['report'] );

    // Configurar los parámetros del correo.
    $to      = get_option( 'admin_email' );
    $subject = sprintf(
        'You have a report for post "%s"',
        get_the_title( $report['post_id'] )
    );
    $headers = mwpm_get_report_email_headers( $report );
    $message = mwpm_build_report_email_message( $report );

    // Enviar el correo con el reporte.
    wp_mail( $to, $subject, $message, $headers );

}
add_action( 'wp', 'mwpm_maybe_send_report' );

/**
 * Retorna un array de headers para el correo de notificación que será
 * enviado al administrador.
 *
 * @since 1.0.0
 */
function mwpm_get_report_email_headers( $report ) {
    $headers = array();

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $report['email'];
    $headers[] = 'Reply-To: ' . $report['email'];
    $headers[] = 'Content-Type: text/html';

    return $headers;
}

/**
 * Construye el contenido para el correo de notificación que será
 * enviado al administrador.
 *
 * @since 1.0.0
 */
function mwpm_build_report_email_message( $report ) {
    $message  = '';
    $message .= 'Hi there Admin,' . '<br/><br/>';
    $message .= sprintf(
        '%s has reported your post.',
        $report['name']
    );
    $message .= '<br/><br/>';
    $message .= 'Additional information for this report is included below:' . '<br/><br/>';
    $message .= '<i>' . nl2br( $report['reason'] ) . '</i>' . '<br/><br/>';
    $message .= 'You can edit the post clicking the following link: ';

    $url = admin_url( 'post.php?post=' . $report['post_id'] . '&action=edit' );

    $message .= '<a href="' . $url . '">' . $url . '</a>';

    return $message;
}
