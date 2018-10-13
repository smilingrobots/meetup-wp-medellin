<?php
/**
 * Plugin Name: Meetup Wordpress Medellín 2018
 * Plugin URI: https://github.com/smilingrobots/meetup-wp-medellin
 * Description: Plugin de prueba para el Taller de introducción al desarrollo de plugins para WordPress.
 * Version: 1.0.0
 */

/**
 * Función principal utilizada para hacer las verficaciones iniciales y
 * configurar las acciones y filtros que soportan la funcionalidad del
 * plugin.
 *
 * @since 1.0.0
 */
function mwpm_wordpress_configured() {
    // El formulario para reportar entradas solo será mostrado en páginas y
    // publicaciones individuales.
    if ( is_single() || is_page() ) {
        add_filter( 'the_content', 'mwpm_add_report_button_to_content' );
        add_action( 'wp_enqueue_scripts', 'mwpm_enqueue_style' );
        add_action( 'wp_footer', 'mwpm_enqueue_script' );
    }
}
add_action( 'wp', 'mwpm_wordpress_configured' );

function mwpm_generate_button_for_post( $post_id ) {
    $user_id = get_current_user_id();
    $button  = '';
    $button .= '<button id="reportBtn">';
    $button .= '<span class="dashicons dashicons-flag"></span>';
    $button .= ' ';
    $button .= 'Report</button>';

    return $button;
} 

function mwpm_add_report_button_to_content( $content ) {
    $like_box  = '';
    $like_box .= '<p>';
    $like_box .= mwpm_generate_button_for_post( get_the_ID() );
    $like_box .= '</p>';

    $report_modal = mwpm_render_report_modal();

    return $like_box . $content . $report_modal;
}

function mwpm_maybe_send_report() {
    // Validar que existan los datos del reporte.
    if ( empty( $_POST['report'] ) ) {
        return;
    }

    // Sanitizar los datos del reporte sumisitrados por el usuario en el formulario.
    $report  = stripslashes_deep( $_POST['report'] );

    // Configurar los parámetros del correo
    $to      = get_option( 'admin_email' );
    $subject = sprintf (
        __( 'You have a report from your post %s', 'mwpm' ),
        get_the_title( $report['post_id'] )
    );
    $headers = mwpm_get_headers( $report );

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

    // Enviar el correo con el reporte.
    wp_mail( $to, $subject, $message, $headers );

}
add_action( 'wp', 'mwpm_maybe_send_report' );

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

            <form action="/" method="post">
                <input type="hidden" name="report[post_id]" value="' . get_the_ID() . '" />
                <p>
                    <label>' . __( 'Your Name', 'mwpm' ) . '</label>
                    <input type="text" name="report[name]" placeholder="Willy" required/>
                </p>
                <p>
                    <label>' . __( 'Your Email', 'mwpm' ) . '</label>
                    <input type="email" name="report[email]" placeholder="w@willy.com" required/>
                </p>

                <p>' . __( 'Please enter the reasons to report this post:', 'mwpm' ) . '</p>
                <textarea name="report[reason]" value="" placeholder="' . __( 'Additional info.', 'mwpm' ) . '" required></textarea>
                <br/>
                <p>
                    <input class="btn" type="submit" value="' . __( 'Report', 'mwpm' ) . '" />
                </p>
            </form>
        </div>
    </div>';

    return $modal;
}

function mwpm_enqueue_style() {
    wp_enqueue_style(
        'mwpm-styles',
        plugins_url( '/', __FILE__ ) . 'style.css',
        false
    ); 
}

function mwpm_enqueue_script() {
    wp_enqueue_script(
        'mwpm-script',
        plugins_url( '/', __FILE__ ) . 'mwpm_script.js',
        false
    );
}

function mwpm_get_headers( $report ) {
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $report['email'];
    $headers[] = 'Reply-To: ' . $report['email'];
    $headers[] = 'Content-Type: text/html';

    return $headers;
}
