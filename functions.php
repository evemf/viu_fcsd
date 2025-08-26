<?php
/**
 * Theme bootstrap.
 */

require_once get_template_directory() . '/inc/i18n.php';

add_action( 'after_setup_theme', function () {
    load_theme_textdomain( 'viu-fcsd', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( [
        'primary' => __( 'Primary Menu', 'viu-fcsd' ),
    ] );
} );

add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( 'viu-fcsd', get_stylesheet_uri(), [], '0.0.1' );
    wp_enqueue_style( 'viu-fcsd-main', get_template_directory_uri() . '/assets/css/main.css', [], '0.0.1' );
    wp_enqueue_script( 'viu-fcsd-main', get_template_directory_uri() . '/assets/js/main.js', [ 'wp-i18n' ], '0.0.1', true );
} );

add_action( 'after_switch_theme', function () {
    flush_rewrite_rules();
} );
