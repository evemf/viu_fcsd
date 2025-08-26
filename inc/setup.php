<?php
/**
 * Theme setup and assets.
 */

if ( ! function_exists( 'viu_fcsd_setup' ) ) {
    function viu_fcsd_setup() {
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

        add_editor_style( 'assets/css/style.css' );
    }
}
add_action( 'after_setup_theme', 'viu_fcsd_setup' );

if ( ! function_exists( 'viu_fcsd_enqueue_assets' ) ) {
    function viu_fcsd_enqueue_assets() {
        $style_path  = get_template_directory() . '/assets/css/style.css';
        $script_path = get_template_directory() . '/assets/js/app.js';

        wp_enqueue_style(
            'viu-fcsd-style',
            get_template_directory_uri() . '/assets/css/style.css',
            [],
            filemtime( $style_path )
        );

        wp_enqueue_script(
            'viu-fcsd-app',
            get_template_directory_uri() . '/assets/js/app.js',
            [],
            filemtime( $script_path ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'viu_fcsd_enqueue_assets' );
