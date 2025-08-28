<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Ajustes legales (Customizer) + Shortcodes
 * Shortcodes disponibles para insertar en páginas:
 * [org_name] [org_cif] [org_address] [org_website] [dpo_email] [policy_date]
 */

function viu_legal_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'viu_legal_section', [
        'title'       => __( 'Legal (Tema)', 'viu-fcsd' ),
        'priority'    => 46,
        'description' => __( 'Datos de la entidad para páginas legales.', 'viu-fcsd' ),
    ] );

    // Valores por defecto (FCSD)
    $default_name   = 'FUNDACIÓ PRIVADA CATALANA SÍNDROME DE DOWN';
    $default_cif    = 'G08897696';
    $default_web    = home_url( '/' );
    $default_dpo    = 'protecciondedatos@fcsd.org';
    $default_date   = '20 de març de 2019';

    $wp_customize->add_setting( 'viu_legal_org_name', [
        'default'           => $default_name,
        'sanitize_callback' => 'wp_strip_all_tags',
    ] );
    $wp_customize->add_control( 'viu_legal_org_name', [
        'section' => 'viu_legal_section',
        'label'   => __( 'Nombre de la entidad', 'viu-fcsd' ),
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'viu_legal_cif', [
        'default'           => $default_cif,
        'sanitize_callback' => 'wp_strip_all_tags',
    ] );
    $wp_customize->add_control( 'viu_legal_cif', [
        'section' => 'viu_legal_section',
        'label'   => __( 'CIF/NIF', 'viu-fcsd' ),
        'type'    => 'text',
    ] );

    // Dirección: reaprovechamos la del customizer existente si está creada (viu_fcsd_address).
    // (Si no existiera, puedes crear otro campo similar)
    if ( ! get_theme_mod( 'viu_fcsd_address' ) ) {
        set_theme_mod( 'viu_fcsd_address', "Comte Borrell, 201–203, entresòl\n08029 Barcelona" );
    }

    $wp_customize->add_setting( 'viu_legal_website', [
        'default'           => $default_web,
        'sanitize_callback' => 'esc_url_raw',
    ] );
    $wp_customize->add_control( 'viu_legal_website', [
        'section' => 'viu_legal_section',
        'label'   => __( 'URL del sitio', 'viu-fcsd' ),
        'type'    => 'url',
    ] );

    $wp_customize->add_setting( 'viu_legal_dpo', [
        'default'           => $default_dpo,
        'sanitize_callback' => 'sanitize_email',
    ] );
    $wp_customize->add_control( 'viu_legal_dpo', [
        'section' => 'viu_legal_section',
        'label'   => __( 'Email DPO/DPD', 'viu-fcsd' ),
        'type'    => 'text',
    ] );

    $wp_customize->add_setting( 'viu_legal_policy_date', [
        'default'           => $default_date,
        'sanitize_callback' => 'wp_strip_all_tags',
    ] );
    $wp_customize->add_control( 'viu_legal_policy_date', [
        'section' => 'viu_legal_section',
        'label'   => __( 'Fecha “Política actualizada”', 'viu-fcsd' ),
        'type'    => 'text', // puedes cambiar a date si prefieres
    ] );
}
add_action( 'customize_register', 'viu_legal_customize_register' );

/* ===== Shortcodes ===== */
function viu_sc_org_name(){ return esc_html( get_theme_mod( 'viu_legal_org_name' ) ); }
add_shortcode('org_name','viu_sc_org_name');

function viu_sc_org_cif(){ return esc_html( get_theme_mod( 'viu_legal_cif' ) ); }
add_shortcode('org_cif','viu_sc_org_cif');

function viu_sc_org_address(){
    $addr = get_theme_mod( 'viu_fcsd_address', "Comte Borrell, 201–203, entresòl\n08029 Barcelona" );
    return nl2br( esc_html( $addr ) );
}
add_shortcode('org_address','viu_sc_org_address');

function viu_sc_org_website(){ return esc_html( untrailingslashit( get_theme_mod( 'viu_legal_website', home_url('/') ) ) ); }
add_shortcode('org_website','viu_sc_org_website');

function viu_sc_dpo_email(){ return esc_html( get_theme_mod( 'viu_legal_dpo' ) ); }
add_shortcode('dpo_email','viu_sc_dpo_email');

function viu_sc_policy_date(){ return esc_html( get_theme_mod( 'viu_legal_policy_date' ) ); }
add_shortcode('policy_date','viu_sc_policy_date');
