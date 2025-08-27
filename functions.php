<?php
/**
 * Theme bootstrap.
 */

require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/setup.php';

// Al activar el tema, refrescar las rewrite rules (útil para i18n routing).
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );
