<?php
/**
 * Customizer: Ajustes de contacto (dirección para footer y mapa)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

function viu_fcsd_customize_register_address( $wp_customize ) {

    // Sección nueva en el Personalizador.
    $wp_customize->add_section( 'viu_fcsd_contact_section', [
        'title'       => __( 'Contacto (Tema)', 'viu-fcsd' ),
        'priority'    => 45,
        'description' => __( 'Dirección de la entidad. Se usa en el footer y en el mapa.', 'viu-fcsd' ),
    ] );

    // Ajuste: dirección (multilínea).
    $wp_customize->add_setting( 'viu_fcsd_address', [
        'default'           => "Comte Borrell, 201–203, entresòl\n08029 Barcelona",
        'sanitize_callback' => function( $value ) {
            // Permite múltiples líneas; limpia HTML.
            $value = wp_strip_all_tags( $value );
            // Limita longitud por seguridad.
            return mb_substr( $value, 0, 300 );
        },
        'transport'         => 'refresh',
    ] );

    // Control (textarea) para la dirección.
    $wp_customize->add_control( 'viu_fcsd_address_control', [
        'label'       => __( 'Dirección postal', 'viu-fcsd' ),
        'section'     => 'viu_fcsd_contact_section',
        'settings'     => 'viu_fcsd_address',
        'type'        => 'textarea',
        'input_attrs' => [
            'rows'        => 3,
            'placeholder' => "Calle, número, piso\nCP Ciudad",
        ],
    ] );
}
add_action( 'customize_register', 'viu_fcsd_customize_register_address' );
