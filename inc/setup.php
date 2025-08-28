<?php
/**
 * Theme setup and assets.
 */

if ( ! function_exists( 'viu_fcsd_setup' ) ) {
    function viu_fcsd_setup() {
        load_theme_textdomain( 'viu-fcsd', get_template_directory() . '/languages' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'custom-logo' );
        add_theme_support( 'editor-styles' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption' ] );

        register_nav_menus( [
            'primary' => __( 'Primary Menu', 'viu-fcsd' ),
            'footer'  => __( 'Footer Menu', 'viu-fcsd' ),
        ] );

        add_editor_style( 'assets/css/style.css' );
    }
}
add_action( 'after_setup_theme', 'viu_fcsd_setup' );

if ( ! function_exists( 'viu_fcsd_enqueue_assets' ) ) {
    function viu_fcsd_enqueue_assets() {
        $theme_style = get_stylesheet_directory() . '/style.css';
        $style_path  = get_template_directory() . '/assets/css/style.css';
        $script_path = get_template_directory() . '/assets/js/app.js';

        wp_enqueue_style(
            'viu-fcsd-base',
            get_stylesheet_uri(),
            [],
            file_exists( $theme_style ) ? filemtime( $theme_style ) : null
        );

        wp_enqueue_style(
            'viu-fcsd-style',
            get_template_directory_uri() . '/assets/css/style.css',
            [ 'viu-fcsd-base' ],
            file_exists( $style_path ) ? filemtime( $style_path ) : null
        );

        wp_enqueue_script(
            'viu-fcsd-app',
            get_template_directory_uri() . '/assets/js/app.js',
            [],
            file_exists( $script_path ) ? filemtime( $script_path ) : null,
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'viu_fcsd_enqueue_assets' );

// Allow SVG uploads so the custom logo can use that format.
if ( ! function_exists( 'viu_fcsd_allow_svg_upload' ) ) {
    function viu_fcsd_allow_svg_upload( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }
}
add_filter( 'upload_mimes', 'viu_fcsd_allow_svg_upload' );
