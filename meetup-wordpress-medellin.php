<?php
/**
 * Plugin Name: Meetup Wordpress Medellín 2018
 * Plugin URI: https://github.com/smilingrobots/meetup-wp-medellin
 * Description: Plugin de prueba para el Taller de introducción al desarrollo de plugins para WordPress.
 * Version: 1.0.0
 */

function mwpm_generate_button_for_post( $post_id ) {
    $button  = '';
    $button .= '<button id="reportBtn">';
    $button .= '<span class="dashicons dashicons-flag"></span>';
    $button .= ' ';
    $button .= 'Report</button>';

    return $button;
} 

function mwpm_add_report_button_to_content( $content ) {
    // El formulario para reportar entradas solo será mostrado en páginas y
    // publicaciones individuales.
    if ( is_single() || is_page() ) {
        $report_button  = '';
        $report_button .= '<p>';
        $report_button .= mwpm_generate_button_for_post( get_the_ID() );
        $report_button .= '</p>';

        $report_modal = mwpm_render_report_modal();

        return $report_button . $content . $report_modal;
    }

    return $content;
}
add_filter( 'the_content', 'mwpm_add_report_button_to_content' );

function mwpm_maybe_send_report() {
    // Validar que existan los datos del reporte.
    if ( empty( $_POST['report'] ) ) {
        return;
    }

    // Remover slashes (/) de los datos del reporte suministrados por el
    // usuario en el formulario.
    $report = stripslashes_deep( $_POST['report'] );

    // Configurar los parámetros del correo.
    $to      = get_option( 'admin_email' );
    $subject = sprintf (
        __( 'You have a report from your post %s', 'mwpm' ),
        get_the_title( $report['post_id'] )
    );
    $headers = mwpm_get_headers( $report );
    $message = mwpm_build_report_email_message( $report );

    // Enviar el correo con el reporte.
    wp_mail( $to, $subject, $message, $headers );

}
add_action( 'wp', 'mwpm_maybe_send_report' );

/**
 * @since 1.0.0
 */
function mwpm_build_report_email_message( $report ) {
    $message  = '';
    $message .= __( 'Hi there Admin,', 'mwpm' );
    $message .= '<br/><br/>';
    $message .= sprintf( 
        __( '%s has reported your post.', 'mwpm' ), 
        $report['name']
    );
    $message .= '<br/><br/>';
    $message .= sprintf( 
        __( 'Additional information for this report: <i>"%s"</i>', 'mwpm' ),
        $report['reason']
    );
    $message .= '<br/><br/>';
    $message .= __( 'You can edit this post in the following link: ', 'mwpm' );

    $url = admin_url( 'post.php?post=' . $report['post_id'] . '&action=edit' );

    $message .= '<a href="' . $url . '">' . $url . '</a>';

    return $message;
}

/**
 * Crea el código HTML para el formulario que los usuarios podrán utilizar
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
            ' . get_modal_form() .
        '</div>
    </div>';

    return $modal;
}

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

function mwpm_get_headers( $report ) {
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $report['email'];
    $headers[] = 'Reply-To: ' . $report['email'];
    $headers[] = 'Content-Type: text/html';

    return $headers;
}

function get_modal_form() {
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

    // Crear campo del nombre de quien reporta
    /* translators: %s es el nombre de usuario del usuario actual. */
    $name_label  = sprintf( __( 'Your Name: %s', 'mwpm' ), $username );
    $name_input  = '<label for="mwpm-report-form__name">' . esc_html( $name_label ) . '</label>';
    $name_input .= sprintf(
        '<input id="mwpm-report-form__name" type="%s" name="report[name]" placeholder="María Pérez" value="%s" required/>',
        $username ? 'hidden' : 'text',
        $username ? esc_attr( $username ) : ''
    );

    // Crear campo del correo de quien reporta
    /* translators: %s es la dirección de correo electrónico del usuario actual. */
    $email_label  = sprintf( __( 'Your Email: %s', 'mwpm' ), $user_email );
    $email_input  = '<label for="mwpm-report-form__email">' . esc_html( $email_label ) . '</label>';

    $email_input .= sprintf( 
        '<input id="mwpm-report-form__email" type="%s" name="report[email]" placeholder="maria.perez@example.org" value="%s" required/>',
        $user_email ? 'hidden' : 'email',
        $user_email ? esc_attr( $user_email ) : ''
    );

    // Crear formulario.
    $form = '<form action="" method="post">
        <input type="hidden" name="report[post_id]" value="' . get_the_ID() . '" />
        <p>
            ' . $name_input . '
        </p>
        <p>
            ' . $email_input . '
        </p>

        <p>
            <label for="mwpm-report-form__reason">' . esc_html__( 'Please enter the reasons to report this post:', 'mwpm' ) . '</label>
            <textarea id="mwpm-report-form__reason" name="report[reason]" value="" placeholder="' . esc_attr__( 'Additional info.', 'mwpm' ) . '" required></textarea>
        </p>
        <p>
            <input class="btn" type="submit" value="' . esc_attr__( 'Report', 'mwpm' ) . '" />
        </p>
    </form>';

    return $form;
}
