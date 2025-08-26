<?php
/**
 * Theme bootstrap.
 */

require_once get_template_directory() . '/inc/i18n.php';

if ( file_exists( get_template_directory() . '/inc/setup.php' ) ) {
    require_once get_template_directory() . '/inc/setup.php';
} else {
    add_action( 'after_setup_theme', function () {
        load_theme_textdomain( 'viu-fcsd', get_template_directory() . '/languages' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'editor-styles' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption' ] );

        register_nav_menus( [
            'primary' => __( 'Primary Menu', 'viu-fcsd' ),
            'footer'  => __( 'Footer Menu', 'viu-fcsd' ),
        ] );

        // Estilos del editor basados en el CSS del tema.
        add_editor_style( 'assets/css/style.css' );
    } );

    add_action( 'wp_enqueue_scripts', function () {
        // Cache-busting por mtime; carga style.css (cabecera del tema) + assets compilados.
        $theme_style_path = get_stylesheet_directory() . '/style.css';
        $style_path       = get_template_directory() . '/assets/css/style.css';
        $script_path      = get_template_directory() . '/assets/js/app.js';

        if ( file_exists( $theme_style_path ) ) {
            wp_enqueue_style(
                'viu-fcsd',
                get_stylesheet_uri(),
                [],
                filemtime( $theme_style_path )
            );
        }

        if ( file_exists( $style_path ) ) {
            wp_enqueue_style(
                'viu-fcsd-style',
                get_template_directory_uri() . '/assets/css/style.css',
                [],
                filemtime( $style_path )
            );
        }

        if ( file_exists( $script_path ) ) {
            wp_enqueue_script(
                'viu-fcsd-app',
                get_template_directory_uri() . '/assets/js/app.js',
                [ 'wp-i18n' ], // quita 'wp-i18n' si no lo usas en el JS
                filemtime( $script_path ),
                true
            );
        }
    } );
}

// Al activar el tema, refrescar las rewrite rules (útil para i18n routing).
add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );
