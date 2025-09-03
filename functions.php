<?php
/**
 * Theme bootstrap.
 */

require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/setup.php';
require_once get_template_directory() . '/inc/services.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/store.php';
require_once get_template_directory() . '/inc/ml-pages.php';
require_once get_template_directory() . '/inc/legal-settings.php';
require_once get_template_directory() . '/inc/account.php';



// Al activar el tema, refrescar las rewrite rules (útil para i18n routing).
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );

// Fuentes / iconos por CDN.
add_action(
    'wp_enqueue_scripts',
    function () {
        wp_enqueue_style(
            'viu-fcsd-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap',
            [],
            null
        );

        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css',
            [],
            '4.3.0'
        );
    },
    5
);
